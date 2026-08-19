<?php

declare(strict_types=1);

namespace Modules\General\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ponytail: starter deny-list of confirmed corrupted tokens (UFARM-2770).
 * Add new bad tokens here as future content QA finds them.
 */
class NoContentCorruption implements ValidationRule
{
    private const CORRUPTED_TOKENS = [
        'qiumat', 'uon', 'qauta', "g'ouasi", 'uoki',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        foreach (self::CORRUPTED_TOKENS as $token) {
            if (preg_match('/(?<![\p{L}])'.preg_quote($token, '/').'(?![\p{L}])/ui', $value) === 1) {
                $fail(__('general::filament.resources.article.validation.corrupted_token', ['token' => $token]));

                return;
            }
        }

        // ponytail: targets comma-separated ID/reference lists (e.g. "52085,52028,52006"); requires
        // 3+ digits each side so it doesn't flag legitimate uz/ru decimal commas like "3,5 kg".
        if (preg_match('/\d{3,},\d{3,}/', $value) === 1) {
            $fail(__('general::filament.resources.article.validation.comma_spacing'));
        }
    }
}
