<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport\Worker;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Region;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Models\AiImportAlias;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Models\ServiceCategory;
use Modules\JobsServices\Services\AiImport\AiExtractionClient;
use Modules\JobsServices\Services\AiImport\BlockSplitter;
use Modules\JobsServices\Services\AiImport\CategoryCreator;
use Modules\JobsServices\Services\AiImport\ExtractionFailedException;
use Modules\JobsServices\Services\AiImport\ExtractionResult;
use Modules\JobsServices\Services\AiImport\SheetFetcher;
use Modules\JobsServices\Services\AiImport\SpreadsheetFlattener;
use Modules\JobsServices\Services\AiImport\TaxonomyAiResolver;

/**
 * Runs one worker import from source to staged rows (UFARM-2644).
 *
 * Fetch → flatten to TSV → split into blocks → extract each → map → resolve
 * whatever deterministic matching missed → create the categories nothing could
 * resolve to. Nothing here writes to the real tables; that is the publisher's
 * job, after a human has reviewed the result. The one exception is a category
 * created for review, which is inserted inactive precisely so it is not yet
 * real to anyone but the admin looking at it.
 *
 * Kept out of the queued job so the pipeline can be exercised directly in a
 * test without a queue, and so the job stays about retries and status.
 */
final class WorkerImportParser
{
    public function __construct(
        private readonly AiExtractionClient $client = new AiExtractionClient,
        private readonly SheetFetcher $fetcher = new SheetFetcher,
        private readonly SpreadsheetFlattener $flattener = new SpreadsheetFlattener,
        private readonly BlockSplitter $splitter = new BlockSplitter,
        private readonly TaxonomyAiResolver $aiResolver = new TaxonomyAiResolver,
        private readonly CategoryCreator $categoryCreator = new CategoryCreator,
        private readonly OfferLocaleTranslator $localeTranslator = new OfferLocaleTranslator,
    ) {}

    /**
     * @return array{rows: array<int, array<string, mixed>>, result: ExtractionResult, tsv: string, expected_rows: int, extracted_rows: int}
     *
     * @throws ExtractionFailedException
     */
    public function parse(AiImportBatch $batch): array
    {
        $tsv = $this->readSource($batch);

        $chunks = $this->splitter->split(
            $tsv,
            (int) config('services.openrouter.max_input_chars', 60000),
        );

        $result = new ExtractionResult;

        foreach ($chunks as $index => $chunk) {
            $result = $result->merge(
                $this->extractChunk($batch, $chunk, $index + 1, count($chunks)),
            );
        }

        $rows = (new WorkerRowMapper)->map($result->records);
        $localized = $this->localeTranslator->translateRows($rows);
        $rows = $localized['rows'];

        // Compared against the STAGED rows, not the raw records. A model that
        // loses its place tends to repeat a row rather than stop, so the raw
        // count can match the sheet exactly while distinct workers are missing
        // — dedup then quietly absorbs the difference. Counting what survives
        // mapping is what an admin actually gets (UFARM-2671).
        //
        // The sheet legitimately repeats one worker per service they offer,
        // and those rows differ by title so they survive dedup; only genuinely
        // identical rows fold, and those are the ones worth reporting.
        $expected = $this->splitter->countDataRows($tsv);
        $extracted = count($rows);

        if ($extracted < $expected) {
            Log::warning('AI import staged fewer rows than the sheet holds', [
                'batch_id' => $batch->getKey(),
                'expected' => $expected,
                'staged' => $extracted,
                'raw_records' => $result->count(),
            ]);
        }

        // Taxonomy resolution costs one more call, so it runs once over the
        // whole batch rather than per chunk, and only over what is still
        // unmatched after deterministic matching.
        $usage = $localized['result']->merge($this->resolveLeftovers($rows));

        return [
            'rows' => $rows,
            'result' => $result->merge($usage),
            'tsv' => $tsv,
            'expected_rows' => $expected,
            'extracted_rows' => $extracted,
        ];
    }

    /**
     * Extract one chunk, retrying once if the model returned fewer distinct
     * workers than the chunk visibly contains.
     *
     * A short answer is the failure this importer is most prone to and least
     * able to see: the JSON is well-formed, the rows present are correct, and
     * the missing ones leave no trace. Since the chunk's row count is known,
     * one retry is cheap next to an admin discovering the gap by hand — or
     * never discovering it. The fuller of the two attempts wins, so a retry
     * can only improve the result.
     *
     * Distinct, not raw: a model that loses its place repeats a row at least
     * as often as it stops, and a duplicate would otherwise make a short
     * answer look complete.
     *
     * @throws ExtractionFailedException
     */
    private function extractChunk(AiImportBatch $batch, string $chunk, int $part, int $total): ExtractionResult
    {
        $expected = $this->splitter->countDataRows($chunk);

        $result = $this->client->extract(
            WorkerExtractionPrompt::messages($chunk, $part, $total, $expected),
            WorkerExtractionSchema::responseFormat(),
            WorkerExtractionSchema::COLLECTION_KEY,
        );

        if (self::distinctCount($result) >= $expected) {
            return $result;
        }

        Log::warning('AI import chunk came back short; retrying once', [
            'batch_id' => $batch->getKey(),
            'part' => $part,
            'expected' => $expected,
            'distinct' => self::distinctCount($result),
            'raw' => $result->count(),
        ]);

        $retry = $this->client->extract(
            WorkerExtractionPrompt::messages($chunk, $part, $total, $expected),
            WorkerExtractionSchema::responseFormat(),
            WorkerExtractionSchema::COLLECTION_KEY,
        );

        // Usage from both attempts is kept either way — the second call was
        // paid for whether or not its records are the ones used.
        return self::distinctCount($retry) > self::distinctCount($result)
            ? new ExtractionResult(
                records: $retry->records,
                model: $retry->model,
                promptTokens: self::sum($result->promptTokens, $retry->promptTokens),
                completionTokens: self::sum($result->completionTokens, $retry->completionTokens),
                cost: self::sumFloat($result->cost, $retry->cost),
            )
            : new ExtractionResult(
                records: $result->records,
                model: $result->model,
                promptTokens: self::sum($result->promptTokens, $retry->promptTokens),
                completionTokens: self::sum($result->completionTokens, $retry->completionTokens),
                cost: self::sumFloat($result->cost, $retry->cost),
            );
    }

    /**
     * Distinct workers in a reply, folded the way the mapper will fold them:
     * one person may legitimately appear once per service they offer, so the
     * phone alone is not the identity of a row.
     */
    private static function distinctCount(ExtractionResult $result): int
    {
        $seen = [];

        foreach ($result->records as $record) {
            $seen[implode('|', [
                preg_replace('/\D+/', '', (string) ($record['phone'] ?? '')),
                mb_strtolower(trim((string) ($record['service_title'] ?? ''))),
            ])] = true;
        }

        return count($seen);
    }

    private static function sum(?int $a, ?int $b): ?int
    {
        return $a === null && $b === null ? null : (int) $a + (int) $b;
    }

    private static function sumFloat(?float $a, ?float $b): ?float
    {
        return $a === null && $b === null ? null : (float) $a + (float) $b;
    }

    /**
     * @throws ExtractionFailedException
     */
    private function readSource(AiImportBatch $batch): string
    {
        if ($batch->source_type === 'google_sheet') {
            $csv = $this->fetcher->fetchCsv((string) $batch->source_url);

            $path = tempnam(sys_get_temp_dir(), 'ufarm_import_');

            if ($path === false) {
                throw ExtractionFailedException::sourceUnreadable('could not buffer the sheet locally');
            }

            try {
                file_put_contents($path, $csv);

                return $this->flattener->flatten($path, 'csv');
            } finally {
                @unlink($path);
            }
        }

        $disk = Storage::disk((string) ($batch->disk ?: config('filesystems.default')));

        if (! $disk->exists((string) $batch->path)) {
            throw ExtractionFailedException::sourceUnreadable('the uploaded file is no longer on disk');
        }

        $extension = strtolower(pathinfo((string) $batch->original_filename, PATHINFO_EXTENSION) ?: 'csv');

        // OpenSpout reads from a real path, and remote disks have none —
        // copy to a temp file so uploads work regardless of the disk driver.
        $path = tempnam(sys_get_temp_dir(), 'ufarm_import_');

        if ($path === false) {
            throw ExtractionFailedException::sourceUnreadable('could not buffer the upload locally');
        }

        try {
            file_put_contents($path, $disk->get((string) $batch->path));

            return $this->flattener->flatten($path, $extension);
        } finally {
            @unlink($path);
        }
    }

    /**
     * Hand still-unmatched category and region names to the AI resolver, then
     * fold whatever it resolves back into the staged rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function resolveLeftovers(array &$rows): ExtractionResult
    {
        $usage = new ExtractionResult;

        $categoryNames = $this->unresolvedNames($rows, 'category_id', 'category_name');

        if ($categoryNames !== []) {
            $response = $this->aiResolver->resolve(
                AiImportAlias::TAXONOMY_SERVICE_CATEGORY,
                $categoryNames,
                $this->categoryCandidates(),
            );

            $this->applyResolution($rows, $response['resolved'], 'category_id', 'category_name', 'category_matched_by');
            $usage = $usage->merge($this->usageResult($response['usage']));
        }

        // Last resort, and only for what nothing above could match: the sheet
        // describes a service the catalog has no word for yet. Creating it —
        // inactive, for an admin to review — is what stops an otherwise clean
        // import from stranding every worker in that block (UFARM-2671).
        $stillUnmatched = $this->unresolvedNames($rows, 'category_id', 'category_name');

        if ($stillUnmatched !== []) {
            $response = $this->categoryCreator->create($stillUnmatched);

            $this->applyResolution(
                $rows,
                $response['created'],
                'category_id',
                'category_name',
                'category_matched_by',
                created: true,
            );
            $usage = $usage->merge($this->usageResult($response['usage']));
        }

        $regionNames = $this->unresolvedNames($rows, 'region_id', 'region_name');

        if ($regionNames !== []) {
            $response = $this->aiResolver->resolve(
                AiImportAlias::TAXONOMY_REGION,
                $regionNames,
                $this->regionCandidates(),
            );

            $this->applyResolution($rows, $response['resolved'], 'region_id', 'region_name', 'location_matched_by');
            $usage = $usage->merge($this->usageResult($response['usage']));
        }

        return $usage;
    }

    /**
     * Distinct names still without an id — the resolver is priced per name,
     * not per row, so a sheet repeating one category 30 times costs once.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, string>
     */
    private function unresolvedNames(array $rows, string $idKey, string $nameKey): array
    {
        $names = [];

        foreach ($rows as $row) {
            if (($row['data'][$idKey] ?? null) !== null) {
                continue;
            }

            $name = $row['data'][$nameKey] ?? null;

            if (is_string($name) && $name !== '') {
                $names[$name] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $resolved
     * @param  bool  $created  the id names a category this import just made
     */
    private function applyResolution(
        array &$rows,
        array $resolved,
        string $idKey,
        string $nameKey,
        string $matchedByKey,
        bool $created = false,
    ): void {
        if ($resolved === []) {
            return;
        }

        $validator = new WorkerRowValidator;

        foreach ($rows as &$row) {
            if (($row['data'][$idKey] ?? null) !== null) {
                continue;
            }

            $name = $row['data'][$nameKey] ?? null;

            if (! is_string($name) || ! isset($resolved[$name])) {
                continue;
            }

            $row['data'][$idKey] = $resolved[$name];
            $row['data'][$matchedByKey] = 'ai';

            if ($created) {
                // Flagged so the review screen can say the category is new and
                // still inactive, rather than presenting it as a match.
                $row['data']['category_created'] = true;
            }

            // Re-derived rather than spliced: the gap this filled is no longer
            // an error, and asking the validator is how the row's problems are
            // decided everywhere else.
            $row['errors'] = $validator->validate($row['data']);
        }

        unset($row);
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function categoryCandidates(): array
    {
        return ServiceCategory::query()
            // Inactive included, for the same reason the deterministic index
            // includes them: a category an earlier import created and nobody
            // has activated yet is still the right answer, and hiding it would
            // only cause it to be created again.
            ->whereIn('applies_to', ['offer', 'both'])
            ->get()
            ->map(static fn (ServiceCategory $category): array => [
                'id' => (int) $category->getKey(),
                // Every translation, so the model can match whichever
                // language the source sheet happens to be written in.
                'label' => implode(' | ', array_filter(array_values($category->getTranslations('name')))),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function regionCandidates(): array
    {
        return Region::query()
            ->get()
            ->map(static fn (Region $region): array => [
                'id' => (int) $region->getKey(),
                'label' => implode(' | ', array_filter(array_values($region->getTranslations('name')))),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $usage
     */
    private function usageResult(array $usage): ExtractionResult
    {
        return new ExtractionResult(
            promptTokens: isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null,
            completionTokens: isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null,
            cost: isset($usage['cost']) ? (float) $usage['cost'] : null,
        );
    }

    /**
     * Statuses a batch may be parsed from. Guards a queue retry from wiping
     * rows an admin has already corrected.
     */
    public static function isParsable(AiImportBatch $batch): bool
    {
        return in_array($batch->status, [AiImportStatus::Pending, AiImportStatus::Failed], true);
    }
}
