<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\AiImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Services\AiImport\Worker\WorkerImportParser;
use Throwable;

/**
 * Extracts one uploaded/linked spreadsheet into staged rows (UFARM-2644).
 *
 * Queued because an LLM call over a whole sheet takes far longer than a web
 * request should: the admin uploads, gets a "queued" notification, and the
 * review screen polls until the rows appear.
 *
 * `ShouldBeUnique` keyed on the batch stops a double-click from paying for the
 * same extraction twice.
 */
class ParseAiImportJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * A chunked sheet is several sequential OpenRouter calls (each capped at
     * `openrouter.timeout`, 120s), plus a retry for any chunk that comes back
     * short, plus taxonomy resolution. The old 300s covered the single call
     * this used to make and would now cut a large sheet off mid-import
     * (UFARM-2671).
     */
    public int $timeout = 900;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    /**
     * Long enough to cover three attempts plus backoff. Tied to `timeout`: if
     * the lock expired first, a second dispatch could start a duplicate — and
     * duplicates here are paid for twice.
     */
    public int $uniqueFor = 3000;

    public function __construct(public readonly int $batchId)
    {
        // Deliberately NOT a dedicated `imports` queue. This is the only job
        // in the repo that would need one, so a named queue silently requires
        // a supervisor change nobody is told about — and a batch that lands on
        // an unwatched queue sits at "Queued" forever with no error to explain
        // it. The extraction is slow (up to 300s), so if it starts crowding
        // other work, split it out *with* the worker config in the same change.
        $this->onQueue(config('queue.ai_import_queue') ?: null);
    }

    public function uniqueId(): string
    {
        return (string) $this->batchId;
    }

    public function handle(): void
    {
        $batch = AiImportBatch::find($this->batchId);

        if ($batch === null) {
            return;
        }

        // The feature flag can be switched off while this sits in the queue.
        if (! AiImport::usable()) {
            $this->markFailed($batch, __('admin-panel.resources.ai_import.errors.not_configured'));

            return;
        }

        // Never re-extract over rows an admin has already reviewed.
        if (! WorkerImportParser::isParsable($batch)) {
            return;
        }

        $batch->forceFill([
            'status' => AiImportStatus::Processing,
            'error_message' => null,
        ])->save();

        try {
            $parsed = (new WorkerImportParser)->parse($batch);
        } catch (Throwable $e) {
            // Let the queue retry transient provider failures; only record a
            // terminal failure once the attempts are spent.
            if ($this->attempts() < $this->tries) {
                // A retry re-enters through isParsable(), which deliberately
                // rejects Processing batches to protect reviewed rows. Put
                // this failed attempt back into the queued state before
                // rethrowing so Laravel's next attempt can actually run.
                $batch->forceFill([
                    'status' => AiImportStatus::Pending,
                    'error_message' => null,
                ])->save();

                throw $e;
            }

            $this->markFailed($batch, $e->getMessage());

            return;
        }

        $this->store($batch, $parsed);
    }

    /**
     * @param  array{rows: array<int, array<string, mixed>>, result: \Modules\JobsServices\Services\AiImport\ExtractionResult, tsv: string, expected_rows: int, extracted_rows: int}  $parsed
     */
    private function store(AiImportBatch $batch, array $parsed): void
    {
        $rows = $parsed['rows'];
        $result = $parsed['result'];

        $valid = count(array_filter(
            $rows,
            static fn (array $row): bool => ($row['errors']['blocking'] ?? []) === [],
        ));

        DB::transaction(function () use ($batch, $rows, $result, $parsed, $valid): void {
            // A re-parse replaces the previous attempt wholesale.
            $batch->rows()->delete();

            foreach (array_chunk($rows, 200) as $chunk) {
                $batch->rows()->createMany($chunk);
            }

            $batch->forceFill([
                'status' => AiImportStatus::Extracted,
                'rows_total' => count($rows),
                'rows_valid' => $valid,
                'model_used' => $result->model,
                'prompt_tokens' => $result->promptTokens,
                'completion_tokens' => $result->completionTokens,
                'cost' => $result->cost,
                'stats' => [
                    ...(array) $batch->stats,
                    // Kept so a short extraction is visible on the review
                    // screen rather than only in the log.
                    'expected_rows' => $parsed['expected_rows'],
                    'extracted_rows' => $parsed['extracted_rows'],
                ],
                // Kept for diagnosis, bounded so a huge sheet cannot bloat the row.
                'extracted_text' => mb_substr($parsed['tsv'], 0, 20000),
                'parsed_at' => now(),
                'error_message' => null,
            ])->save();
        });

        Log::info('AI import parsed', [
            'batch_id' => $batch->getKey(),
            'entity_type' => $batch->entity_type->value,
            'rows' => count($rows),
            'valid' => $valid,
            'expected' => $parsed['expected_rows'],
            'model' => $result->model,
            'cost' => $result->cost,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $batch = AiImportBatch::find($this->batchId);

        if ($batch === null || $batch->status === AiImportStatus::Published) {
            return;
        }

        $this->markFailed($batch, $exception?->getMessage() ?? 'unknown error');
    }

    private function markFailed(AiImportBatch $batch, string $message): void
    {
        $batch->forceFill([
            'status' => AiImportStatus::Failed,
            'error_message' => mb_substr($message, 0, 1000),
        ])->save();

        Log::warning('AI import failed', [
            'batch_id' => $batch->getKey(),
            'message' => $message,
        ]);
    }
}
