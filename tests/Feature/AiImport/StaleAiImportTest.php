<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Filament\Resources\AiImportResource\Pages\ReviewAiImport;
use Modules\JobsServices\Models\AiImportBatch;
use Tests\TestCase;

/**
 * Recovery for a batch no worker ever picked up (UFARM-2644).
 *
 * Found on admin-dev: the job was dispatched to a named queue nothing was
 * consuming, so the batch sat at "Queued" indefinitely — not extracted, so it
 * could not publish; still "in progress", so it could not re-parse. Stranded
 * with no way out of the UI.
 */
class StaleAiImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

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
    }

    public function test_a_freshly_queued_batch_is_not_offered_a_retry(): void
    {
        $batch = $this->batch(AiImportStatus::Pending);

        // A worker may simply be busy; offering "re-run" immediately would
        // invite a second paid extraction of the same sheet.
        $this->assertFalse($batch->isStale());
        $this->assertTrue($batch->isInProgress());
        $this->assertFalse($batch->isReparsable());
    }

    public function test_a_batch_nobody_picked_up_becomes_retryable(): void
    {
        $batch = $this->batch(AiImportStatus::Pending);
        $batch->forceFill(['created_at' => now()->subMinutes(AiImportBatch::STALE_AFTER_MINUTES + 1)])->save();

        $this->assertTrue($batch->isStale());
        $this->assertFalse($batch->isInProgress());
        // The whole point: an admin can unstick it without an engineer.
        $this->assertTrue($batch->isReparsable());
    }

    public function test_a_worker_that_died_mid_run_becomes_retryable(): void
    {
        $batch = $this->batch(AiImportStatus::Processing);
        $batch->forceFill(['updated_at' => now()->subMinutes(AiImportBatch::STALE_AFTER_MINUTES + 1)])->save();

        $this->assertTrue($batch->isStale());
        $this->assertTrue($batch->isReparsable());
    }

    public function test_a_slow_but_live_extraction_is_not_declared_dead(): void
    {
        // The job's own timeout is 300s plus backoff; the staleness window is
        // comfortably past that, so a long extraction is never interrupted.
        $batch = $this->batch(AiImportStatus::Processing);
        $batch->forceFill(['updated_at' => now()->subMinutes(5)])->save();

        $this->assertFalse($batch->isStale());
        $this->assertFalse($batch->isReparsable());
    }

    public function test_a_published_batch_is_never_reparsable(): void
    {
        // Re-parsing would discard rows that already became real workers.
        $batch = $this->batch(AiImportStatus::Published);
        $batch->forceFill(['created_at' => now()->subDay()])->save();

        $this->assertFalse($batch->isReparsable());
    }

    public function test_a_failed_batch_is_reparsable_immediately(): void
    {
        $this->assertTrue($this->batch(AiImportStatus::Failed)->isReparsable());
    }

    public function test_a_partly_published_batch_is_never_reparsable(): void
    {
        // It sits at Extracted so the rest can still be published, but its
        // published rows point at real users and offers, and re-parsing
        // deletes every staged row — orphaning them (UFARM-2671).
        $batch = $this->batch(AiImportStatus::Extracted);
        $batch->forceFill(['rows_published' => 4])->save();

        $this->assertFalse($batch->isReparsable());
    }

    public function test_polling_stops_once_a_batch_is_stale(): void
    {
        // Otherwise the review page polls a dead batch forever.
        $batch = $this->batch(AiImportStatus::Pending);
        $batch->forceFill(['created_at' => now()->subMinutes(AiImportBatch::STALE_AFTER_MINUTES + 1)])->save();

        $this->assertFalse($batch->isInProgress());

        $page = app(ReviewAiImport::class);
        $page->record = $batch;

        $this->assertNull($page->getPollingInterval());
    }

    private function batch(AiImportStatus $status): AiImportBatch
    {
        return AiImportBatch::create([
            'uuid' => (string) Str::uuid(),
            'entity_type' => AiImportEntity::WORKER,
            'source_type' => 'google_sheet',
            'status' => $status,
        ]);
    }
}
