<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

/**
 * Parsers for the raw values an import sheet actually contains (UFARM-2644).
 *
 * Every rule here answers a specific defect observed in the real onboarding
 * spreadsheet, not a hypothetical one. Kept separate from the mapper so each
 * can be unit-tested against the exact strings that appear in the source.
 */
final class FieldParser
{
    /** Uzbekistan's country calling code. */
    private const COUNTRY_CODE = '998';

    /** Subscriber number length inside Uzbekistan. */
    private const LOCAL_LENGTH = 9;

    /** Uzbekistan's bounding box, generous enough for border districts. */
    private const LAT_MIN = 37.0;

    private const LAT_MAX = 46.0;

    private const LNG_MIN = 55.0;

    private const LNG_MAX = 74.0;

    /**
     * Normalise a phone to `998XXXXXXXXX`.
     *
     * The sheet writes numbers four ways: "998 99 444-42-00", "998937810004",
     * "95 514 08 08" (no country code) and "9989099170007" (thirteen digits —
     * a typo, one digit too many).
     *
     * That last shape is deliberately NOT repaired. Any fix would be a guess
     * about which digit is spurious, and guessing wrong silently attaches a
     * worker to a stranger's phone number — the identity this import keys
     * everything on. It is flagged for a human instead.
     *
     * @return array{phone: string|null, error: string|null}
     */
    public static function phone(?string $raw): array
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';

        if ($digits === '') {
            return ['phone' => null, 'error' => 'phone_missing'];
        }

        $expected = strlen(self::COUNTRY_CODE) + self::LOCAL_LENGTH;

        if (strlen($digits) === $expected && str_starts_with($digits, self::COUNTRY_CODE)) {
            return ['phone' => $digits, 'error' => null];
        }

        if (strlen($digits) === self::LOCAL_LENGTH) {
            return ['phone' => self::COUNTRY_CODE.$digits, 'error' => null];
        }

        // Leading 0 before the subscriber number is a common local habit.
        if (strlen($digits) === self::LOCAL_LENGTH + 1 && str_starts_with($digits, '0')) {
            return ['phone' => self::COUNTRY_CODE.substr($digits, 1), 'error' => null];
        }

        if (strlen($digits) > $expected && str_starts_with($digits, self::COUNTRY_CODE)) {
            return ['phone' => null, 'error' => 'phone_ambiguous'];
        }

        return ['phone' => null, 'error' => 'phone_invalid'];
    }

    /**
     * Parse a coordinate.
     *
     * The sheet quotes latitudes with a trailing comma and stray spaces
     * ("41.258827,", " 41.340831, ") and occasionally uses a comma as the
     * decimal separator ("41,258827"). Values outside Uzbekistan's bounding
     * box are rejected rather than stored, since a coordinate in the wrong
     * hemisphere is worse than no coordinate at all — `service_offers`
     * requires one, and the mapper has sane fallbacks.
     */
    public static function latitude(mixed $raw): ?float
    {
        return self::coordinate($raw, self::LAT_MIN, self::LAT_MAX);
    }

    public static function longitude(mixed $raw): ?float
    {
        return self::coordinate($raw, self::LNG_MIN, self::LNG_MAX);
    }

    private static function coordinate(mixed $raw, float $min, float $max): ?float
    {
        if (is_float($raw) || is_int($raw)) {
            $value = (float) $raw;

            return $value >= $min && $value <= $max ? $value : null;
        }

        if (! is_string($raw)) {
            return null;
        }

        $clean = trim($raw);
        $clean = trim($clean, " \t\n\r\0\x0B,;");

        if ($clean === '') {
            return null;
        }

        // "41,258827" is a decimal comma; "41,258,827" would be grouping, but
        // coordinates are never grouped, so a lone comma is always the point.
        if (substr_count($clean, ',') === 1 && ! str_contains($clean, '.')) {
            $clean = str_replace(',', '.', $clean);
        }

        $clean = str_replace([' ', ','], '', $clean);

        if (! is_numeric($clean)) {
            return null;
        }

        $value = (float) $clean;

        return $value >= $min && $value <= $max ? $value : null;
    }

    /**
     * Parse a price cell into a number, or null when it does not state one.
     *
     * Handles "200 000", "200000", "45 000,50", "45,000.50" and "1 200 000
     * сум". Returns null for "По договору" and for multi-line tiered prices
     * ("До 20га - 200 000 / Больше 20га - 150 000"), which state several
     * prices rather than one — the caller keeps the original text and marks
     * the offer negotiable.
     */
    public static function price(mixed $raw): ?float
    {
        if (is_float($raw) || is_int($raw)) {
            return (float) $raw;
        }

        if (! is_string($raw)) {
            return null;
        }

        $value = trim($raw);

        if ($value === '' || self::isBlankMarker($value)) {
            return null;
        }

        // Several numbers means several prices — a tier table, not a price.
        if (preg_match_all('/\d[\d\s\x{00A0}.,]*/u', $value, $matches) !== 1) {
            return null;
        }

        $number = trim($matches[0][0]);
        $number = str_replace(["\u{00A0}", ' '], '', $number);

        if ($number === '') {
            return null;
        }

        $lastComma = strrpos($number, ',');
        $lastDot = strrpos($number, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Whichever separator comes last is the decimal point.
            $number = $lastComma > $lastDot
                ? str_replace('.', '', substr_replace($number, '.', $lastComma, 1))
                : str_replace(',', '', $number);
        } elseif ($lastComma !== false) {
            // A lone comma with exactly two trailing digits is decimal;
            // otherwise it groups thousands ("45,000").
            $number = strlen($number) - $lastComma === 3
                ? substr_replace($number, '.', $lastComma, 1)
                : str_replace(',', '', $number);
        } elseif ($lastDot !== false && strlen($number) - $lastDot !== 3) {
            // "45.000" in this data is 45000, not 45.
            $decimals = strlen($number) - $lastDot - 1;

            if ($decimals === 3) {
                $number = str_replace('.', '', $number);
            }
        }

        return is_numeric($number) ? (float) $number : null;
    }

    /**
     * Parse a "years of experience" cell. "5", "5 йил", "5 лет" -> 5.0.
     */
    public static function experienceYears(mixed $raw): ?float
    {
        if (is_float($raw) || is_int($raw)) {
            $value = (float) $raw;

            return $value >= 0 && $value <= 99 ? $value : null;
        }

        if (! is_string($raw) || self::isBlankMarker($raw)) {
            return null;
        }

        if (preg_match('/\d+(?:[.,]\d+)?/', $raw, $match) !== 1) {
            return null;
        }

        $value = (float) str_replace(',', '.', $match[0]);

        return $value >= 0 && $value <= 99 ? $value : null;
    }

    /**
     * The sheet uses several placeholders for "nothing here".
     */
    public static function isBlankMarker(?string $value): bool
    {
        $value = trim((string) $value);

        return in_array(mb_strtolower($value), ['', '-', '—', '–', 'null', 'n/a', 'нет', 'yoq'], true);
    }

    /**
     * Trim a value, collapsing the sheet's blank markers to null.
     */
    public static function text(mixed $raw, ?int $maxLength = null): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $value = trim($raw);

        if (self::isBlankMarker($value)) {
            return null;
        }

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            return mb_substr($value, 0, $maxLength);
        }

        return $value;
    }
}
