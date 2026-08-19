<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\JobsServices\Models\AiImportAlias;
use Modules\JobsServices\Services\AiImport\TaxonomyAiResolver;
use Tests\TestCase;

/**
 * The safety properties of AI taxonomy resolution (UFARM-2644).
 *
 * The load-bearing one is `test_an_id_outside_the_candidate_set_is_rejected`:
 * without it, a hallucinated-but-real-looking id would silently attach workers
 * to the wrong category, and nothing on the review screen would show it.
 */
class TaxonomyAiResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated sqlite: the real tables' migrations live in ufarm-api
        // (shared-DB convention), so the suite builds just what it needs.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('ai_import_aliases', function ($table): void {
            $table->id();
            $table->string('taxonomy');
            $table->string('source');
            $table->string('source_norm');
            $table->unsignedBigInteger('target_id');
            $table->string('origin')->default('ai');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['taxonomy', 'source_norm']);
        });

        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.category_match_threshold' => 0.8,
            'services.openrouter.taxonomy_resolution' => true,
        ]);
    }

    public function test_a_confident_match_is_accepted_but_not_remembered(): void
    {
        $this->fakeMatches([['source_name' => 'Услуги Дрона', 'target_id' => 2, 'confidence' => 0.95]]);

        $result = $this->resolve();

        $this->assertSame(['Услуги Дрона' => 2], $result['resolved']);

        // Deliberately NOT remembered. An alias outranks every matching pass
        // but an exact catalog hit, so caching a guess turned one wrong answer
        // into a permanent, invisible fact for every later import — that is how
        // "Услуги Орошения" resolved to "Tractor services" on admin-dev
        // (UFARM-2671). A guess is re-made and re-checked each time; only a
        // human's correction is durable.
        $this->assertNull(AiImportAlias::lookup(AiImportAlias::TAXONOMY_SERVICE_CATEGORY, 'Услуги Дрона'));
        $this->assertSame(0, AiImportAlias::count());
    }

    public function test_an_id_outside_the_candidate_set_is_rejected(): void
    {
        // A well-formed integer at full confidence that was never offered.
        // Validating membership of the exact set sent is stronger than
        // "does this id exist", because a hallucinated id that happens to be
        // a real category would still pass an existence check.
        $this->fakeMatches([['source_name' => 'Услуги Дрона', 'target_id' => 999, 'confidence' => 1.0]]);

        $this->assertSame([], $this->resolve()['resolved']);
        $this->assertSame(0, AiImportAlias::count());
    }

    public function test_a_low_confidence_match_is_dropped(): void
    {
        $this->fakeMatches([['source_name' => 'Услуги Дрона', 'target_id' => 2, 'confidence' => 0.4]]);

        $this->assertSame([], $this->resolve()['resolved']);
    }

    public function test_an_explicit_null_is_respected(): void
    {
        // "No catalog entry means this" is a correct answer, not a failure.
        $this->fakeMatches([['source_name' => 'Услуги Дрона', 'target_id' => null, 'confidence' => 0.9]]);

        $this->assertSame([], $this->resolve()['resolved']);
    }

    public function test_a_string_id_is_not_silently_coerced(): void
    {
        $this->fakeMatches([['source_name' => 'Услуги Дрона', 'target_id' => '2', 'confidence' => 0.99]]);

        $this->assertSame([], $this->resolve()['resolved']);
    }

    public function test_a_provider_failure_degrades_quietly(): void
    {
        // Taxonomy resolution is a convenience; it must never fail an
        // extraction that has already been paid for.
        Http::fake(['*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $this->assertSame([], $this->resolve()['resolved']);
    }

    public function test_nothing_unresolved_means_no_request(): void
    {
        Http::fake();

        $result = (new TaxonomyAiResolver)->resolve(
            AiImportAlias::TAXONOMY_SERVICE_CATEGORY,
            [],
            $this->candidates(),
        );

        $this->assertSame([], $result['resolved']);
        Http::assertNothingSent();
    }

    public function test_it_can_be_switched_off_entirely(): void
    {
        config(['services.openrouter.taxonomy_resolution' => false]);
        Http::fake();

        $this->assertSame([], $this->resolve()['resolved']);
        Http::assertNothingSent();
    }

    public function test_an_admin_mapping_is_never_downgraded(): void
    {
        AiImportAlias::remember(AiImportAlias::TAXONOMY_REGION, 'Тошкент ш', 1, AiImportAlias::ORIGIN_ADMIN, 7);
        AiImportAlias::remember(AiImportAlias::TAXONOMY_REGION, 'Тошкент ш', 99, AiImportAlias::ORIGIN_AI);

        // Nothing writes an AI alias any more, but the guard stays: a human
        // fixing a bad match must make it stick.
        $this->assertSame(1, AiImportAlias::lookup(AiImportAlias::TAXONOMY_REGION, 'Тошкент ш'));
    }

    public function test_admin_aliases_are_found_across_scripts(): void
    {
        AiImportAlias::remember(AiImportAlias::TAXONOMY_REGION, 'Тошкент шаҳри', 1, AiImportAlias::ORIGIN_ADMIN);

        // The sheet writes Cyrillic; the catalog stores Latin Uzbek.
        $this->assertSame(1, AiImportAlias::lookup(AiImportAlias::TAXONOMY_REGION, 'Toshkent shahri'));
    }

    public function test_an_ai_alias_left_by_an_older_import_is_ignored(): void
    {
        // Rows written before AI guesses stopped being cached are still in the
        // table on every deployed environment. They must stop being obeyed, or
        // the wrong match they encode outlives the fix (UFARM-2671).
        AiImportAlias::remember(AiImportAlias::TAXONOMY_SERVICE_CATEGORY, 'Услуги Орошения', 1, AiImportAlias::ORIGIN_AI);

        $this->assertNull(AiImportAlias::lookup(AiImportAlias::TAXONOMY_SERVICE_CATEGORY, 'Услуги Орошения'));
    }

    /**
     * @return array{resolved: array<string, int>, usage: array<string, mixed>}
     */
    private function resolve(): array
    {
        return (new TaxonomyAiResolver)->resolve(
            AiImportAlias::TAXONOMY_SERVICE_CATEGORY,
            ['Услуги Дрона' => 'Услуги Дрона'],
            $this->candidates(),
        );
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function candidates(): array
    {
        return [
            ['id' => 7, 'label' => 'Irrigation'],
            ['id' => 2, 'label' => 'Spraying'],
            ['id' => 8, 'label' => 'Other services'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     */
    private function fakeMatches(array $matches): void
    {
        Http::fake(['*' => Http::response([
            'model' => 'openai/gpt-4o-mini',
            'choices' => [['message' => ['content' => json_encode(['matches' => $matches])]]],
            'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 20, 'cost' => 0.0004],
        ])]);
    }
}
