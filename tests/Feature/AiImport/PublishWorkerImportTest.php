<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Models\UserProfile;
use Modules\JobsServices\Services\AiImport\Worker\PublishWorkerImportAction;
use Tests\TestCase;

/**
 * Publishing staged rows into real workers, profiles and offers (UFARM-2644).
 */
class PublishWorkerImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated sqlite: the real tables' migrations live in ufarm-api
        // (shared-DB convention), so the suite builds just what it needs.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('language')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_details', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('language')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_profiles', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->text('bio')->nullable();
            $table->decimal('experience_years', 4, 1)->default(0);
            $table->text('specializations')->nullable();
            $table->boolean('is_available')->default(true);
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('service_offers', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->text('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('price_unit');
            $table->string('currency')->default('UZS');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('status')->default('open');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ai_import_batches', function ($table): void {
            $table->id();
            $table->uuid('uuid');
            $table->string('entity_type');
            $table->string('source_type');
            $table->string('status')->default('pending');
            $table->text('stats')->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_valid')->default(0);
            $table->unsignedInteger('rows_published')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_import_rows', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('ai_import_batch_id');
            $table->unsignedInteger('row_index')->default(0);
            $table->string('label')->nullable();
            $table->string('group_label')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->text('data')->nullable();
            $table->text('raw')->nullable();
            $table->text('errors')->nullable();
            $table->string('status')->default('draft');
            $table->string('created_type')->nullable();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->timestamps();
        });

        // `service_offers.city`/`region` are free text, so the publisher looks
        // up the localized names of the ids it resolved.
        Schema::create('regions', function ($table): void {
            $table->id();
            $table->text('name');
            $table->boolean('is_active')->default(true);
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lng', 10, 7)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cities', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('region_id');
            $table->text('name');
            $table->boolean('is_active')->default(true);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('regions')->insert([
            'id' => 1,
            'name' => json_encode(['uz' => 'Toshkent shahri', 'ru' => 'город Ташкент', 'en' => 'Tashkent city']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cities')->insert([
            'id' => 8,
            'region_id' => 1,
            'name' => json_encode(['uz' => 'Chilonzor tumani', 'ru' => 'Чиланзарский район', 'en' => 'Chilanzar district']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'services.authbridge.jsonrpc.endpoint' => 'https://ufarm-auth.test/api/v1/jsonrpc',
            'services.authbridge.jsonrpc.username' => 'jsonrpc-user',
            'services.authbridge.jsonrpc.password' => 'jsonrpc-secret',
            'services.authbridge.worker_application_alias' => 'in_service',
        ]);
    }

    /**
     * Auth answers findOrCreateUser successfully.
     *
     * Called per test rather than in setUp(): a second Http::fake() does not
     * replace an earlier stub, so a setUp() fake would be unoverridable by the
     * one test that needs auth to fail.
     */
    private function fakeAuthSuccess(): void
    {
        Http::fake(['*' => Http::response([
            'result' => ['success' => true, 'user_id' => 987654, 'created' => true],
        ])]);
    }

    public function test_it_creates_a_worker_with_profile_offer_and_auth_account(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([$this->row(0)]);

        $result = (new PublishWorkerImportAction)->execute($batch);

        $this->assertSame(1, $result['published']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(1, $result['workers_created']);
        $this->assertSame(1, $result['offers_created']);

        $user = User::where('phone', '998994444200')->firstOrFail();
        $this->assertSame('Бахтиер Шакиров', $user->name);
        $this->assertSame(987654, (int) $user->auth_id);

        $this->assertDatabaseHas('user_details', ['user_id' => $user->id, 'region_id' => 1, 'city_id' => 8]);

        $profile = UserProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue((bool) $profile->is_available);
        $this->assertSame('ai_import', data_get($profile->meta, 'origin.type'));
        // The company has no column of its own, so it rides in the bio.
        $this->assertStringContainsString('AGRO TOMCHI', (string) $profile->bio);

        $offer = ServiceOffer::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Томчилатиб суғориш', $offer->getTitleTranslations()['uz']);
        $this->assertSame('open', $offer->status);
    }

    public function test_the_auth_account_is_created_without_a_password(): void
    {
        $this->fakeAuthSuccess();

        // Imported workers sign in by OTP. Sending a password would also mean
        // inventing one and having no way to deliver it.
        (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0)]));

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $body['method'] === 'findOrCreateUser'
                && ! array_key_exists('password', $body['params'])
                && $body['params']['application_alias'] === 'in_service';
        });
    }

    public function test_one_worker_with_two_services_becomes_one_user_and_two_offers(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([
            $this->row(0),
            $this->row(1, ['category_id' => 2, 'title' => 'Дори сепиш']),
        ]);

        $result = (new PublishWorkerImportAction)->execute($batch);

        $this->assertSame(2, $result['published']);
        $this->assertSame(1, User::where('phone', '998994444200')->count());
        $this->assertSame(2, ServiceOffer::count());
    }

    public function test_it_publishes_every_configured_offer_locale(): void
    {
        config(['app.api_locales' => ['uz', 'ru', 'en', 'kk']]);
        $this->fakeAuthSuccess();

        (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0, [
            'title_translations' => [
                'uz' => 'Purkash',
                'ru' => 'Опрыскивание',
                'en' => 'Spraying',
                'kk' => 'Бүрку',
            ],
            'description_translations' => [
                'uz' => 'Narx kelishiladi',
                'ru' => 'Цена договорная',
                'en' => 'Price is negotiable',
                'kk' => 'Бағасы келісімді',
            ],
        ])]));

        $offer = ServiceOffer::firstOrFail();

        $this->assertSame('Бүрку', $offer->getTitleTranslations()['kk']);
        $this->assertSame('Бағасы келісімді', $offer->getDescriptionTranslations()['kk']);
    }

    public function test_publishing_twice_updates_rather_than_duplicates(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([$this->row(0)]);

        (new PublishWorkerImportAction)->execute($batch);
        $batch->rows()->update(['status' => AiImportRowStatus::Draft->value]);
        (new PublishWorkerImportAction)->execute($batch->refresh());

        $this->assertSame(1, User::count());
        $this->assertSame(1, ServiceOffer::count());
        $this->assertSame(1, UserProfile::count());
    }

    public function test_a_row_with_blocking_errors_is_skipped_not_published(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([
            $this->row(0),
            $this->row(1, ['phone' => null], ['blocking' => ['phone_missing'], 'warnings' => []]),
        ]);

        $result = (new PublishWorkerImportAction)->execute($batch);

        $this->assertSame(1, $result['published']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, User::count());
    }

    public function test_a_failing_row_does_not_roll_back_the_others(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([
            $this->row(0),
            // Valid on paper but unpublishable: no category to attach to.
            $this->row(1, ['category_id' => null, 'phone' => '998900000001']),
        ]);

        $result = (new PublishWorkerImportAction)->execute($batch);

        $this->assertSame(1, $result['published']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
        // The good row survived the bad one.
        $this->assertSame(1, ServiceOffer::count());
    }

    public function test_an_unreachable_auth_service_does_not_block_the_import(): void
    {
        // A blank auth_id is a supported "links up on first SSO login" state.
        // Refusing to import because auth blipped would be the worse outcome.
        //
        Http::fake(['*' => Http::response(['error' => ['code' => -32603]], 500)]);

        $result = (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0)]));

        $this->assertSame(1, $result['published']);
        $this->assertNull(User::where('phone', '998994444200')->firstOrFail()->auth_id);
    }

    public function test_an_existing_worker_keeps_curated_values(): void
    {
        $this->fakeAuthSuccess();

        $existing = User::create(['name' => 'Curated Name', 'phone' => '998994444200']);
        UserProfile::create(['user_id' => $existing->id, 'bio' => 'Curated bio', 'experience_years' => 9]);

        (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0)]));

        $existing->refresh();
        $this->assertSame('Curated Name', $existing->name);
        $this->assertSame('Curated bio', UserProfile::where('user_id', $existing->id)->value('bio'));
        $this->assertNull(data_get(UserProfile::where('user_id', $existing->id)->firstOrFail()->meta, 'origin.type'));
    }

    public function test_an_existing_user_becomes_an_ai_added_worker_when_the_profile_is_created(): void
    {
        $this->fakeAuthSuccess();

        $existing = User::create(['name' => 'Existing Farmer', 'phone' => '998994444200']);

        $result = (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0)]));
        $profile = UserProfile::where('user_id', $existing->id)->firstOrFail();

        $this->assertSame(1, $result['workers_created']);
        $this->assertSame('ai_import', data_get($profile->meta, 'origin.type'));
    }

    public function test_publishing_fails_closed_before_the_worker_metadata_migration(): void
    {
        Schema::table('user_profiles', function ($table): void {
            $table->dropColumn('meta');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Worker metadata schema is not available');

        (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0)]));
    }

    public function test_a_negotiable_price_keeps_its_wording_in_the_description(): void
    {
        $this->fakeAuthSuccess();

        (new PublishWorkerImportAction)->execute($this->batchWith([$this->row(0)]));

        // Stored as 0 because the column is NOT NULL, so without this the
        // offer would read "free" rather than "по договору".
        $offer = ServiceOffer::firstOrFail();
        $this->assertSame('0.00', (string) $offer->price);
        $this->assertStringContainsString('По договору', $offer->getDescriptionTranslations()['uz']);
    }

    public function test_a_fully_published_batch_is_marked_published(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([$this->row(0)]);
        $batch->forceFill(['stats' => [
            'expected_rows' => 2,
            'extracted_rows' => 1,
        ]])->save();

        (new PublishWorkerImportAction)->execute($batch);

        $this->assertSame(AiImportStatus::Published, $batch->refresh()->status);
        $this->assertSame(1, $batch->rows_published);
        $this->assertSame(2, $batch->stat('expected_rows'));
        $this->assertSame(1, $batch->stat('extracted_rows'));
    }

    public function test_a_partly_published_batch_stays_open_for_the_rest(): void
    {
        $this->fakeAuthSuccess();

        // The defect this guards (UFARM-2671): a batch whose rows mostly could
        // not publish was still marked Published, which hid the Publish button
        // and stranded every row an admin might have gone on to fix.
        $batch = $this->batchWith([
            $this->row(0),
            $this->row(1, ['phone' => '998900000001'], ['blocking' => ['category_unmatched'], 'warnings' => []]),
        ]);

        (new PublishWorkerImportAction)->execute($batch);

        $batch->refresh();

        $this->assertSame(AiImportStatus::Extracted, $batch->status);
        $this->assertTrue($batch->isPublishable());
        $this->assertSame(1, $batch->rows_published);
    }

    public function test_a_batch_with_published_rows_is_never_reparsable(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([
            $this->row(0),
            $this->row(1, ['phone' => '998900000001'], ['blocking' => ['category_unmatched'], 'warnings' => []]),
        ]);

        (new PublishWorkerImportAction)->execute($batch);

        // Re-parsing deletes every staged row, and the published ones point at
        // real users and offers that would be left orphaned.
        $this->assertFalse($batch->refresh()->isReparsable());
    }

    public function test_fixing_a_row_lets_a_reopened_batch_finish_publishing(): void
    {
        $this->fakeAuthSuccess();

        $batch = $this->batchWith([
            $this->row(0),
            $this->row(1, ['phone' => '998900000001', 'category_id' => null], ['blocking' => ['category_unmatched'], 'warnings' => []]),
        ]);

        (new PublishWorkerImportAction)->execute($batch);

        // What an admin does on the review screen: pick the missing category.
        $stuck = $batch->rows()->where('row_index', 1)->firstOrFail();
        $stuck->forceFill([
            'data' => [...$stuck->data, 'category_id' => 7],
            'errors' => ['blocking' => [], 'warnings' => []],
        ])->save();

        $second = (new PublishWorkerImportAction)->execute($batch->refresh());

        $this->assertSame(1, $second['published']);
        $this->assertSame(AiImportStatus::Published, $batch->refresh()->status);
        $this->assertSame(2, $batch->rows_published);
        $this->assertSame(2, ServiceOffer::count());
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function batchWith(array $rows): AiImportBatch
    {
        $batch = AiImportBatch::create([
            'uuid' => (string) Str::uuid(),
            'entity_type' => AiImportEntity::WORKER,
            'source_type' => 'upload',
            'status' => AiImportStatus::Extracted,
        ]);

        $batch->rows()->createMany($rows);

        return $batch->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<string, array<int, string>>|null  $errors
     * @return array<string, mixed>
     */
    private function row(int $index, array $overrides = [], ?array $errors = null): array
    {
        $data = [
            'company' => 'AGRO TOMCHI',
            'person_name' => 'Бахтиер Шакиров',
            'phone' => '998994444200',
            'language' => 'uz',
            'bio' => null,
            'experience_years' => null,
            'specializations' => ['Орошение'],
            'working_hours' => '09:00-18:00',
            'region_id' => 1,
            'region_name' => 'Тошкент ш',
            'city_id' => 8,
            'city_name' => 'Чилонзор тумани',
            'address' => 'Бунёдкор шоҳ кўчаси, 29',
            'latitude' => 41.258827,
            'longitude' => 69.194217,
            'coords_source' => 'sheet',
            'category_id' => 7,
            'category_name' => 'Орошение',
            'title' => 'Томчилатиб суғориш',
            'description' => null,
            'price' => 0.0,
            'price_raw' => 'По договору',
            'price_negotiable' => true,
            'price_unit' => 'per_project',
            'currency' => 'UZS',
            'area' => 'Га',
            ...$overrides,
        ];

        return [
            'row_index' => $index,
            'label' => $data['person_name'] ?? $data['company'],
            'dedupe_key' => $data['phone'],
            'data' => $data,
            'errors' => $errors ?? ['blocking' => [], 'warnings' => []],
            'status' => AiImportRowStatus::Draft,
        ];
    }
}
