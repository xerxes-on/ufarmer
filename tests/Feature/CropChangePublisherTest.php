<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ModelChangedEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\ParentCrop;
use Modules\Crops\Observers\CropChangePublisher;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Crop edits made in the admin panel must reach RabbitMQ consumers.
 *
 * The image case is the one that regressed silently: spatie/media-library
 * writes to the `media` table without touching the owning model, so an
 * image-only edit fires no Crop::updated event and published nothing.
 *
 * These assert against the transactional outbox — where ShouldPublish events
 * land before the broker — on an isolated in-memory connection, since the
 * crops tables are migrated from ufarm-api.
 */
class CropChangePublisherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The real `crops` table is migrated from ufarm-api (shared-DB
        // convention, see ufarm-core/CLAUDE.md), so stub the columns this
        // publisher reads onto an isolated in-memory connection.
        //
        // The RabbitMQ outbox is pinned to `pgsql` and would try to persist
        // every published event; these tests assert what gets dispatched, not
        // that it reaches the broker, so point the outbox at the same
        // in-memory connection and give it a table.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'rabbitmq.outbox.connection' => 'sqlite',
        ]);
        DB::purge('sqlite');

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('exchange');
            $table->string('routing_key');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('crops', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // image_url resolves through the media library.
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->morphs('model');
            $table->uuid('uuid')->nullable();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->json('manipulations')->nullable();
            $table->json('custom_properties')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->json('responsive_images')->nullable();
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();
        });

        Crop::query()->create([
            'id' => 1,
            'name' => ['uz' => 'Bulgʻor qalampiri', 'en' => 'Pepper'],
            'description' => ['uz' => 'Sabzavot'],
            'is_active' => true,
        ]);

        // Seeding the crop fires the real Crop::saved publisher registered in
        // AppServiceProvider, so clear that row — each test asserts only on
        // what its own action published.
        DB::table('outbox_messages')->truncate();
    }

    public function test_event_uses_the_same_contract_as_ufarm_api(): void
    {
        $event = new ModelChangedEvent('crop', 'updated', ['id' => 1]);

        $this->assertSame('ufarm.crop.updated', $event->routingKey());
        $this->assertSame('ufarm.events', $event->exchange);

        $parent = new ModelChangedEvent('parent_crop', 'updated', ['id' => 1]);
        $this->assertSame('ufarm.parent_crop.updated', $parent->routingKey());
    }

    public function test_publishes_when_a_crop_image_is_attached(): void
    {
        Media::unsetEventDispatcher();

        (new CropChangePublisher)->mediaChanged(
            $this->mediaFor(Crop::class, Crop::MEDIA_COLLECTION_IMAGE),
        );

        $row = DB::table('outbox_messages')->latest('id')->first();

        $this->assertNotNull($row, 'an image-only change published nothing');
        $this->assertSame('ufarm.crop.updated', $row->routing_key);
        $this->assertSame('ufarm.events', $row->exchange);

        $payload = json_decode((string) $row->payload, true)['payload'];

        // The image_url key is what consumers read; ufarm-api's CropObserver
        // sends the same shape.
        $this->assertArrayHasKey('image_url', $payload);
        $this->assertSame(1, $payload['id']);
        $this->assertSame(['uz' => 'Bulgʻor qalampiri', 'en' => 'Pepper'], $payload['name']);
    }

    /**
     * Media rows are shared across every model in the app — articles, user
     * avatars, pest photos. Only crop imagery should publish crop events.
     */
    #[DataProvider('irrelevantMediaProvider')]
    public function test_ignores_media_that_is_not_a_crop_image(string $modelType, string $collection): void
    {
        Media::unsetEventDispatcher();

        (new CropChangePublisher)->mediaChanged($this->mediaFor($modelType, $collection));

        $this->assertSame(0, DB::table('outbox_messages')->count());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function irrelevantMediaProvider(): array
    {
        return [
            'another model entirely' => ['Modules\\General\\Models\\Article', 'crop_image'],
            'crop, but a different collection' => [Crop::class, 'some_other_collection'],
            'parent crop collection on a crop' => [Crop::class, ParentCrop::MEDIA_COLLECTION_IMAGE],
        ];
    }

    private function mediaFor(string $modelType, string $collection): Media
    {
        $media = new Media;
        $media->model_type = $modelType;
        $media->model_id = 1;
        $media->collection_name = $collection;

        return $media;
    }
}
