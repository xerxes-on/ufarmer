<?php

declare(strict_types=1);

namespace Modules\JobsServices\Support;

use Illuminate\Support\Facades\Lang;

final class OfferLocales
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        $configured = config('app.api_locales', config('app.supported_locales', []));
        $configured = is_array($configured) ? $configured : [];

        if (! array_is_list($configured)) {
            $configured = array_keys($configured);
        }

        $locales = array_values(array_unique(array_filter(array_map(
            static fn (mixed $locale): string => strtolower(str_replace('_', '-', trim((string) $locale))),
            $configured,
        ), static fn (string $locale): bool => preg_match('/^[a-z]{2,12}(?:-[a-z0-9]{2,12})*$/', $locale) === 1)));

        return $locales !== [] ? $locales : [self::normalize((string) config('app.locale', 'en'))];
    }

    public static function default(): string
    {
        return self::all()[0];
    }

    public static function label(string $locale): string
    {
        $key = 'admin-panel.locales.'.$locale;

        return Lang::has($key) ? __($key) : strtoupper($locale);
    }

    private static function normalize(string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', trim($locale)));

        return $locale !== '' ? $locale : 'en';
    }
}
