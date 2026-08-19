<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Downloads a linked Google Sheet as CSV (UFARM-2644).
 *
 * The subtle failure this guards against: a sheet that is not link-viewable
 * does not return 403. Google answers with HTTP 200 and its sign-in page, so a
 * naive fetch hands an HTML login form to the extractor and burns an LLM call
 * discovering it isn't a spreadsheet. Detecting it here turns a confusing AI
 * failure into a clear "make the sheet link-viewable" message.
 */
final class SheetFetcher
{
    /**
     * Refuse anything implausible for a worker sheet before buffering it.
     */
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const TIMEOUT_SECONDS = 30;

    /**
     * @throws ExtractionFailedException
     */
    public function fetchCsv(string $url): string
    {
        $exportUrl = GoogleSheetUrl::toCsvExport($url);

        if ($exportUrl === null) {
            throw ExtractionFailedException::sourceUnreadable(
                'the link is not a Google Sheets URL'
            );
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                // Google answers the export endpoint with a 307 to a
                // googleusercontent.com host carrying the actual CSV.
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($exportUrl);
        } catch (Throwable $e) {
            throw ExtractionFailedException::sourceUnreadable($e->getMessage());
        }

        if ($response->failed()) {
            throw ExtractionFailedException::sourceUnreadable(
                sprintf('the sheet responded HTTP %d', $response->status())
            );
        }

        $body = (string) $response->body();

        if (strlen($body) > self::MAX_BYTES) {
            throw ExtractionFailedException::sourceUnreadable('the sheet is too large to import');
        }

        if (trim($body) === '') {
            throw ExtractionFailedException::sourceUnreadable('the sheet is empty');
        }

        if ($this->looksLikeHtml($response->header('Content-Type'), $body)) {
            throw ExtractionFailedException::sourceUnreadable(
                'the sheet is not publicly viewable — share it as "anyone with the link can view"'
            );
        }

        return $body;
    }

    /**
     * A CSV export answers `text/csv`. Anything HTML-shaped is Google's
     * sign-in or error page wearing a 200.
     */
    private function looksLikeHtml(?string $contentType, string $body): bool
    {
        if ($contentType !== null && str_contains(strtolower($contentType), 'text/html')) {
            return true;
        }

        $head = strtolower(ltrim(substr($body, 0, 512)));

        return str_starts_with($head, '<!doctype html')
            || str_starts_with($head, '<html')
            || str_contains($head, '<head>');
    }
}
