<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\JobsServices\Models\AiImportAlias;
use Modules\JobsServices\Models\ServiceCategory;
use Modules\JobsServices\Services\AiImport\CategoryCreator;
use Tests\TestCase;

/**
 * Creating the categories a sheet needs but the catalog lacks (UFARM-2671).
 *
 * Every import of the real onboarding sheet stalled the same way: three of its
 * four service blocks had no category to attach to, so 42 of 46 correctly
 * extracted workers could not be published. These assertions pin the two
 * things that make inventing one safe — it is never active, and it never
 * duplicates a category that already exists.
 */
class CategoryCreatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.model' => 'test/model',
            'services.openrouter.fallback_models' => [],
            'services.openrouter.timeout' => 120,
            'services.openrouter.connect_timeout' => 10,
            'services.openrouter.category_creation' => true,
            'services.openrouter.max_created_categories' => 10,
        ]);
        DB::purge('sqlite');

        Schema::create('service_categories', function ($table): void {
            $table->id();
            $table->text('name');
            $table->string('icon')->nullable();
            $table->string('applies_to', 20)->default('both');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ai_import_aliases', function ($table): void {
            $table->id();
            $table->string('taxonomy', 64);
            $table->string('source');
            $table->string('source_norm');
            $table->unsignedBigInteger('target_id');
            $table->string('origin', 8)->default('ai');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['taxonomy', 'source_norm']);
        });
    }

    public function test_a_category_the_catalog_lacks_is_created_inactive_with_every_translation(): void
    {
        $this->fakeNaming([[
            'source' => 'Услуги Запчастей',
            'uz' => 'Ehtiyot qismlar',
            'ru' => 'Запчасти',
            'en' => 'Spare parts',
        ]]);

        $result = (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей']);

        $category = ServiceCategory::findOrFail($result['created']['Услуги Запчастей']);

        // Inactive is the whole safety story: a category invented from a
        // spreadsheet is a suggestion until an admin says otherwise.
        $this->assertFalse((bool) $category->is_active);
        $this->assertSame('both', $category->applies_to);

        // All three, or it never matches a sheet written in the missing one.
        $this->assertSame('Ehtiyot qismlar', $category->getTranslation('name', 'uz'));
        $this->assertSame('Запчасти', $category->getTranslation('name', 'ru'));
        $this->assertSame('Spare parts', $category->getTranslation('name', 'en'));
    }

    public function test_no_alias_is_written_because_the_category_itself_is_the_record(): void
    {
        // A created category is a real catalog row, so exact matching finds it
        // on the next import for free. Caching the guess as well would
        // re-create the trap that made a wrong AI match permanent — see
        // AiImportAlias::lookup() (UFARM-2671).
        $this->fakeNaming([[
            'source' => 'Услуги Запчастей',
            'uz' => 'Ehtiyot qismlar',
            'ru' => 'Запчасти',
            'en' => 'Spare parts',
        ]]);

        (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей']);

        $this->assertNull(AiImportAlias::lookup(AiImportAlias::TAXONOMY_SERVICE_CATEGORY, 'Услуги Запчастей'));
        $this->assertSame(0, AiImportAlias::count());
    }

    public function test_an_existing_category_is_reused_rather_than_duplicated(): void
    {
        // The resolver missed it on spelling, but the model named the same
        // thing. A second row for one service would be the worse outcome.
        $existing = ServiceCategory::create([
            'name' => ['uz' => 'Ehtiyot qismlari', 'ru' => 'Запчасти', 'en' => 'Spare parts'],
            'applies_to' => 'both',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->fakeNaming([[
            'source' => 'Услуги Запчастей',
            'uz' => 'Ehtiyot qismlar',
            'ru' => 'Запчасти',
            'en' => 'Spare parts',
        ]]);

        $result = (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей']);

        $this->assertSame((int) $existing->getKey(), $result['created']['Услуги Запчастей']);
        $this->assertSame(1, ServiceCategory::count());
    }

    public function test_an_inactive_category_from_an_earlier_import_is_reused_not_recreated(): void
    {
        // Created categories stay inactive until an admin reviews them, which
        // may be days. Every import in between must find the existing one, or
        // each run adds another copy of the same category (UFARM-2671).
        $earlier = ServiceCategory::create([
            'name' => ['uz' => 'Ehtiyot qismlar', 'ru' => 'Запчасти', 'en' => 'Spare parts'],
            'applies_to' => 'both',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $this->fakeNaming([[
            'source' => 'Услуги Запчастей',
            'uz' => 'Ehtiyot qismlar',
            'ru' => 'Запчасти',
            'en' => 'Spare parts',
        ]]);

        $result = (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей']);

        $this->assertSame((int) $earlier->getKey(), $result['created']['Услуги Запчастей']);
        $this->assertSame(1, ServiceCategory::count());
    }

    public function test_a_missing_translation_creates_nothing(): void
    {
        $this->fakeNaming([[
            'source' => 'Услуги Запчастей',
            'uz' => 'Ehtiyot qismlar',
            'ru' => '',
            'en' => 'Spare parts',
        ]]);

        $this->assertSame([], (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей'])['created']);
        $this->assertSame(0, ServiceCategory::count());
    }

    public function test_a_label_nobody_asked_about_is_ignored(): void
    {
        $this->fakeNaming([[
            'source' => 'Something the sheet never said',
            'uz' => 'Aldash',
            'ru' => 'Обман',
            'en' => 'Fabrication',
        ]]);

        $this->assertSame([], (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей'])['created']);
        $this->assertSame(0, ServiceCategory::count());
    }

    public function test_a_provider_failure_leaves_the_rows_unmatched_rather_than_failing_the_import(): void
    {
        // Naming improves an import that already succeeded; it must never
        // break one.
        Http::fake(['*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $this->assertSame([], (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей'])['created']);
        $this->assertSame(0, ServiceCategory::count());
    }

    public function test_it_can_be_switched_off_entirely(): void
    {
        config(['services.openrouter.category_creation' => false]);
        Http::fake();

        $this->assertSame([], (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей'])['created']);
        Http::assertNothingSent();
    }

    public function test_a_config_cached_before_this_feature_existed_does_not_disable_it(): void
    {
        // How this shipped to dev and did nothing (UFARM-2671). A server
        // running `config:cache` from before the key existed has
        // `services.openrouter` as a populated array WITHOUT it, so the lookup
        // returns null — and config()'s default never applies, because the
        // default is only for an absent key. `(bool) null` then silently
        // turned the whole feature off.
        config(['services.openrouter.category_creation' => null]);
        config(['services.openrouter.max_created_categories' => null]);

        $this->fakeNaming([[
            'source' => 'Услуги Запчастей',
            'uz' => 'Ehtiyot qismlar',
            'ru' => 'Запчасти',
            'en' => 'Spare parts',
        ]]);

        $result = (new CategoryCreator)->create(['Услуги Запчастей' => 'Услуги Запчастей']);

        $this->assertArrayHasKey('Услуги Запчастей', $result['created']);
        $this->assertSame(1, ServiceCategory::count());
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     */
    private function fakeNaming(array $categories): void
    {
        Http::fake(['*' => Http::response([
            'model' => 'test/model',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode(['categories' => $categories])],
            ]],
            'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 20, 'cost' => 0.0002],
        ])]);
    }
}
