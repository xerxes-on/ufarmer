<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Models\AiImportRow;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Services\AiImport\Worker\ImportedOfferLocaleBackfill;
use Tests\TestCase;

class ImportedOfferLocaleBackfillTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropAllTables();
        DB::purge('sqlite');

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('service_offers', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ai_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_import_batch_id')->nullable();
            $table->unsignedInteger('row_index')->default(0);
            $table->string('label')->nullable();
            $table->string('group_label')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->json('data');
            $table->json('raw')->nullable();
            $table->json('errors')->nullable();
            $table->string('status');
            $table->string('created_type')->nullable();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_dry_run_targets_only_import_linked_invalid_locale_values(): void
    {
        $candidate = $this->offer(['uz' => 'Same', 'ru' => 'Same', 'en' => 'Same']);
        $cyrillicUz = $this->offer([
            'uz' => 'Йер хайдаш',
            'ru' => 'Вспашка',
            'en' => 'Plowing',
        ], [
            'uz' => 'Yer haydash',
            'ru' => 'Вспашка',
            'en' => 'Plowing',
        ]);
        $incomplete = $this->offer(
            ['uz' => 'Bir', 'ru' => 'Два', 'en' => 'Three'],
            ['en' => 'Description'],
        );
        $untranslatedRussian = $this->offer([
            'uz' => 'Tomchilatib sugʻorish',
            'ru' => 'Томчилатиб суғориш',
            'en' => 'Drip irrigation',
        ], [
            'uz' => 'Tavsif',
            'ru' => 'Описание',
            'en' => 'Description',
        ]);
        $cyrillicDescription = $this->offer(
            ['uz' => 'Texnika xizmatlari', 'ru' => 'Услуги техники', 'en' => 'Machinery services'],
            ['uz' => 'По договору', 'ru' => 'По договору', 'en' => 'By agreement'],
        );
        $curated = $this->offer(
            ['uz' => 'Bir', 'ru' => 'Два', 'en' => 'Three'],
            ['uz' => 'Tavsif', 'ru' => 'Описание', 'en' => 'Description'],
        );
        $descriptionless = $this->offer(
            ['uz' => 'Yer haydash', 'ru' => 'Вспашка', 'en' => 'Plowing'],
            [],
        );
        $mislabeledRussian = $this->offer([
            'uz' => 'Texnika xizmatlari',
            'ru' => 'Услуги техники',
            'en' => 'Machinery services',
        ], [
            'uz' => 'Tavsif',
            'ru' => 'Описание',
            'en' => 'Description',
        ]);
        $this->offer(['uz' => 'Not imported', 'ru' => 'Not imported', 'en' => 'Not imported']);

        $this->row($candidate);
        $this->row($cyrillicUz);
        $this->row($incomplete);
        $this->row($untranslatedRussian, [
            'language' => 'uz',
            'title' => 'Томчилатиб суғориш',
        ]);
        $this->row($cyrillicDescription);
        $this->row($curated);
        $this->row($descriptionless);
        $this->row($mislabeledRussian, [
            'language' => 'uz',
            'title' => 'Услуги техники',
        ]);

        $stats = (new ImportedOfferLocaleBackfill)->execute();

        $this->assertSame(5, $stats['candidates']);
        $this->assertSame(0, $stats['translated']);
        $this->assertSame(0, $stats['title_updates']);
        $this->assertSame(0, $stats['description_updates']);
    }

    /**
     * @param  array<string, string>  $title
     * @param  array<string, string>|null  $description
     */
    private function offer(array $title, ?array $description = null): ServiceOffer
    {
        return ServiceOffer::create([
            'title' => $title,
            'description' => $description ?? ['uz' => 'Same description', 'ru' => 'Same description', 'en' => 'Same description'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function row(ServiceOffer $offer, array $data = []): void
    {
        $titles = $offer->getTitleTranslations();

        AiImportRow::create([
            'data' => array_merge([
                'service_offer_id' => $offer->id,
                'title' => reset($titles),
            ], $data),
            'status' => AiImportRowStatus::Published,
        ]);
    }
}
