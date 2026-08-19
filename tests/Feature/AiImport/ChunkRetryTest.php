<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use Illuminate\Support\Facades\Http;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Services\AiImport\AiExtractionClient;
use Modules\JobsServices\Services\AiImport\BlockSplitter;
use Modules\JobsServices\Services\AiImport\SheetFetcher;
use Modules\JobsServices\Services\AiImport\SpreadsheetFlattener;
use Modules\JobsServices\Services\AiImport\TaxonomyAiResolver;
use Modules\JobsServices\Services\AiImport\Worker\WorkerImportParser;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A chunk the model under-answers is asked again (UFARM-2671).
 *
 * The importer's characteristic failure is a short reply: well-formed JSON,
 * correct rows, and some simply absent. Nothing downstream can see it, so the
 * parser compares each chunk against the row count it can measure itself and
 * pays for one more call rather than losing workers.
 */
class ChunkRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.model' => 'test/model',
            'services.openrouter.fallback_models' => [],
            'services.openrouter.timeout' => 120,
            'services.openrouter.connect_timeout' => 10,
        ]);
    }

    public function test_a_short_chunk_is_retried_and_the_fuller_answer_wins(): void
    {
        $chunk = $this->chunk();
        $expected = (new BlockSplitter)->countDataRows($chunk);

        $this->assertSame(3, $expected);

        Http::fakeSequence()
            ->push($this->completion([['person_name' => 'A']]))
            ->push($this->completion([['person_name' => 'A'], ['person_name' => 'B'], ['person_name' => 'C']]));

        $result = $this->extractChunk($chunk);

        $this->assertSame(3, $result->count());
        Http::assertSentCount(2);
    }

    public function test_a_complete_chunk_is_not_paid_for_twice(): void
    {
        Http::fakeSequence()->push($this->completion([
            ['person_name' => 'A'], ['person_name' => 'B'], ['person_name' => 'C'],
        ]));

        $this->assertSame(3, $this->extractChunk($this->chunk())->count());

        Http::assertSentCount(1);
    }

    public function test_a_retry_that_does_not_help_keeps_the_first_answer_and_both_bills(): void
    {
        // The retry is a chance, not a promise. When it comes back no fuller,
        // the original records stand — but both calls were paid for, so both
        // must reach the batch's recorded cost.
        Http::fakeSequence()
            ->push($this->completion([['person_name' => 'A']], promptTokens: 100, cost: 0.001))
            ->push($this->completion([['person_name' => 'A']], promptTokens: 100, cost: 0.001));

        $result = $this->extractChunk($this->chunk());

        $this->assertSame(1, $result->count());
        $this->assertSame(200, $result->promptTokens);
        $this->assertSame(0.002, $result->cost);
    }

    public function test_the_expected_row_count_is_stated_in_the_prompt(): void
    {
        // Turns "did I get them all?" from a judgement into a check.
        Http::fakeSequence()->push($this->completion([
            ['person_name' => 'A'], ['person_name' => 'B'], ['person_name' => 'C'],
        ]));

        $this->extractChunk($this->chunk());

        Http::assertSent(function ($request): bool {
            $user = collect($request->data()['messages'])->firstWhere('role', 'user');

            return str_contains((string) $user['content'], '3 numbered data row(s)')
                && str_contains((string) $user['content'], 'never invent a row');
        });
    }

    public function test_a_duplicate_does_not_disguise_a_missing_row(): void
    {
        // Observed on admin-dev: a model that loses its place repeats a row
        // rather than stopping, so a raw count can match the sheet exactly
        // while a distinct worker is missing — and dedup then absorbs the
        // difference silently. Counting distinct workers is what catches it.
        Http::fakeSequence()
            ->push($this->completion([
                ['person_name' => 'A', 'phone' => '998900000001', 'service_title' => 'X'],
                ['person_name' => 'A', 'phone' => '998900000001', 'service_title' => 'X'],
                ['person_name' => 'B', 'phone' => '998900000002', 'service_title' => 'Y'],
            ]))
            ->push($this->completion([
                ['person_name' => 'A', 'phone' => '998900000001', 'service_title' => 'X'],
                ['person_name' => 'B', 'phone' => '998900000002', 'service_title' => 'Y'],
                ['person_name' => 'C', 'phone' => '998900000003', 'service_title' => 'Z'],
            ]));

        $result = $this->extractChunk($this->chunk());

        Http::assertSentCount(2);
        $this->assertSame(3, $result->count());
        $this->assertSame(
            ['A', 'B', 'C'],
            array_map(static fn (array $r): string => $r['person_name'], $result->records),
        );
    }

    public function test_one_worker_offering_two_services_is_not_read_as_a_duplicate(): void
    {
        // The sheet legitimately repeats a person once per service. Those rows
        // differ by title, so they must count as two — otherwise every such
        // chunk would look short and be retried for nothing.
        Http::fakeSequence()->push($this->completion([
            ['person_name' => 'A', 'phone' => '998900000001', 'service_title' => 'Irrigation'],
            ['person_name' => 'A', 'phone' => '998900000001', 'service_title' => 'Greenhouses'],
            ['person_name' => 'B', 'phone' => '998900000002', 'service_title' => 'Parts'],
        ]));

        $this->assertSame(3, $this->extractChunk($this->chunk())->count());

        Http::assertSentCount(1);
    }

    private function extractChunk(string $chunk): \Modules\JobsServices\Services\AiImport\ExtractionResult
    {
        $parser = new WorkerImportParser(
            new AiExtractionClient,
            new SheetFetcher,
            new SpreadsheetFlattener,
            new BlockSplitter,
            new TaxonomyAiResolver,
        );

        $method = new ReflectionMethod($parser, 'extractChunk');

        return $method->invoke($parser, $this->batch(), $chunk, 1, 1);
    }

    private function batch(): AiImportBatch
    {
        $batch = new AiImportBatch;
        $batch->forceFill([
            'id' => 1,
            'uuid' => 'test',
            'entity_type' => AiImportEntity::WORKER,
            'source_type' => 'google_sheet',
            'status' => AiImportStatus::Processing,
        ]);

        return $batch;
    }

    private function chunk(): string
    {
        return implode("\n", [
            'Услуги Запчастей',
            "№\tФирма\tИмя\tТелефон",
            "\tФирма\tИмя\tТелефон",
            "1\tPAHTAMASH MChJ\tИброхим Нарзуллаев\t998999995252",
            "2\tMaster.uz\tСадилло\t998973449566",
            "3\tLANDTECH QK MChJ\tМариф Мухаммедхонов\t998977389565",
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $workers
     * @return array<string, mixed>
     */
    private function completion(array $workers, int $promptTokens = 10, float $cost = 0.0001): array
    {
        // Identity is (phone, service_title), so a fixture that names neither
        // would read as one worker repeated. Fill them from the name unless the
        // test is deliberately exercising a collision.
        $workers = array_map(static function (array $worker): array {
            $name = (string) ($worker['person_name'] ?? '');

            return [
                'phone' => '99890000000'.mb_strlen($name).ord($name[0] ?? 'x'),
                'service_title' => $name,
                ...$worker,
            ];
        }, $workers);

        return [
            'model' => 'test/model',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode(['workers' => $workers])],
            ]],
            'usage' => ['prompt_tokens' => $promptTokens, 'completion_tokens' => 5, 'cost' => $cost],
        ];
    }
}
