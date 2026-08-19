<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\JobsServices\Rules\ReadableGoogleSheet;
use Tests\TestCase;

class ReadableGoogleSheetTest extends TestCase
{
    private const SHEET_ID = '1wivHlRc0t4ihmt9a8CTWLdB7hT3X9m6tZdeLO5AkZdE';

    public function test_a_readable_share_link_without_gid_is_accepted(): void
    {
        Http::fake([
            '*' => Http::response("Name\nAlice", 200, ['Content-Type' => 'text/csv']),
        ]);

        $validator = Validator::make([
            'source_url' => $this->shareUrl(),
        ], [
            'source_url' => [new ReadableGoogleSheet],
        ]);

        $this->assertTrue($validator->passes());
        Http::assertSent(fn ($request): bool => $request->url()
            === 'https://docs.google.com/spreadsheets/d/'.self::SHEET_ID.'/gviz/tq?tqx=out:csv');
    }

    public function test_an_unreadable_sheet_is_rejected_before_batch_creation(): void
    {
        Http::fake([
            '*' => Http::response('Not found', 400, ['Content-Type' => 'text/html']),
        ]);

        $validator = Validator::make([
            'source_url' => $this->shareUrl(),
        ], [
            'source_url' => [new ReadableGoogleSheet],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            __('admin-panel.resources.ai_import.errors.unreadable_sheet_url'),
            $validator->errors()->first('source_url'),
        );
    }

    public function test_a_non_google_sheet_url_is_rejected_without_an_http_request(): void
    {
        Http::fake();

        $validator = Validator::make([
            'source_url' => 'https://example.com/workers.csv',
        ], [
            'source_url' => [new ReadableGoogleSheet],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            __('admin-panel.resources.ai_import.errors.invalid_sheet_url'),
            $validator->errors()->first('source_url'),
        );
        Http::assertNothingSent();
    }

    private function shareUrl(): string
    {
        return 'https://docs.google.com/spreadsheets/d/'.self::SHEET_ID.'/edit?usp=sharing';
    }
}
