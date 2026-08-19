<?php

declare(strict_types=1);

namespace Modules\JobsServices\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\JobsServices\Services\AiImport\ExtractionFailedException;
use Modules\JobsServices\Services\AiImport\GoogleSheetUrl;
use Modules\JobsServices\Services\AiImport\SheetFetcher;

final class ReadableGoogleSheet implements ValidationRule
{
    public function __construct(
        private readonly SheetFetcher $fetcher = new SheetFetcher,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (! GoogleSheetUrl::looksLikeSheet($value)) {
            $fail(__('admin-panel.resources.ai_import.errors.invalid_sheet_url'));

            return;
        }

        try {
            $this->fetcher->fetchCsv($value);
        } catch (ExtractionFailedException) {
            $fail(__('admin-panel.resources.ai_import.errors.unreadable_sheet_url'));
        }
    }
}
