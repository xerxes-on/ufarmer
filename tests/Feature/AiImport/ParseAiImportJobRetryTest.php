<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use App\Jobs\ParseAiImportJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Services\AiImport\ExtractionFailedException;
use Tests\TestCase;

class ParseAiImportJobRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.openrouter.worker_import_enabled' => true,
            'services.openrouter.key' => 'test-key',
        ]);
        DB::purge('sqlite');

        Schema::create('ai_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid');
            $table->string('entity_type');
            $table->string('source_type');
            $table->text('source_url')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function test_a_retryable_fetch_failure_returns_the_batch_to_pending(): void
    {
        Http::fake([
            '*' => Http::response('Not found', 400, ['Content-Type' => 'text/html']),
        ]);

        $batch = AiImportBatch::create([
            'uuid' => (string) Str::uuid(),
            'entity_type' => AiImportEntity::WORKER,
            'source_type' => 'google_sheet',
            'source_url' => 'https://docs.google.com/spreadsheets/d/test-sheet-id/edit?usp=sharing',
            'status' => AiImportStatus::Pending,
        ]);

        try {
            (new ParseAiImportJob((int) $batch->getKey()))->handle();
            $this->fail('The fetch failure should be rethrown for Laravel to retry.');
        } catch (ExtractionFailedException) {
            $batch->refresh();

            $this->assertSame(AiImportStatus::Pending, $batch->status);
            $this->assertNull($batch->error_message);
        }
    }
}
