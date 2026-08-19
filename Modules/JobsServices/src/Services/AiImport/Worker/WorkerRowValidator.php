<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport\Worker;

use Modules\JobsServices\Services\AiImport\FieldParser;

/**
 * The single definition of what makes a staged worker row publishable
 * (UFARM-2671).
 *
 * There used to be two: the mapper derived errors while parsing the model's
 * output, and the review screen re-derived them after an admin edited a row.
 * They drifted — an edit silently dropped the region, district and experience
 * warnings, and flattened three distinct phone problems into one — so a row's
 * problems depended on whether anyone had touched it. Both callers now come
 * here.
 *
 * Everything is read back out of the mapped `data`, which is why this can run
 * equally well on a freshly extracted row and on one an admin has just
 * retyped. *Blocking* means the row cannot become a real record; *warnings*
 * mean it can, but somebody should look.
 */
final class WorkerRowValidator
{
    /**
     * Canonical service-price ceiling (UFARM-2767) — must match ufarm-api's
     * config('services.pricing.max_price'). Also referenced by
     * ServicesRelationManager's Filament form and
     * PublishWorkerImportAction's write-path backstop, so this is the one
     * place to change it in this repo.
     */
    public const int MAX_PRICE = 150_000_000;

    /**
     * @param  array<string, mixed>  $data
     * @return array{blocking: array<int, string>, warnings: array<int, string>}
     */
    public function validate(array $data): array
    {
        $blocking = [];
        $warnings = [];

        $phone = $this->phoneError($data);

        if ($phone !== null) {
            $blocking[] = $phone;
        }

        if (($data['category_id'] ?? null) === null) {
            $blocking[] = 'category_unmatched';
        }

        if (blank($data['title'] ?? null)) {
            $blocking[] = 'title_missing';
        }

        if ((float) ($data['price'] ?? 0) > self::MAX_PRICE) {
            $blocking[] = 'price_exceeds_max';
        }

        if (($data['region_id'] ?? null) === null && filled($data['region_name'] ?? null)) {
            $warnings[] = 'region_unmatched';
        }

        if (($data['city_id'] ?? null) === null && filled($data['city_name'] ?? null)) {
            $warnings[] = 'city_unmatched';
        }

        if (($data['coords_source'] ?? 'sheet') !== 'sheet') {
            $warnings[] = 'coords_fallback';
        }

        if ($data['price_negotiable'] ?? false) {
            $warnings[] = 'price_negotiable';
        }

        if (($data['experience_years'] ?? null) === null) {
            $warnings[] = 'experience_missing';
        }

        return [
            'blocking' => array_values(array_unique($blocking)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Re-run the phone rules over whichever value is authoritative.
     *
     * A stored `phone` is already normalised, so it only has to still look
     * normalised. When it is absent the raw cell is re-parsed, which keeps the
     * distinction between "missing", "not a phone" and "too many digits to
     * guess" — the last of those is the one an admin most needs to see, and it
     * used to be reported as a plain invalid number.
     *
     * @param  array<string, mixed>  $data
     */
    private function phoneError(array $data): ?string
    {
        $phone = $data['phone'] ?? null;

        if (is_string($phone) && preg_match('/^\d{12}$/', $phone) === 1) {
            return null;
        }

        if (is_string($phone) && $phone !== '') {
            return FieldParser::phone($phone)['error'] ?? 'phone_invalid';
        }

        $raw = $data['phone_raw'] ?? null;

        return FieldParser::phone(is_string($raw) ? $raw : null)['error'] ?? 'phone_missing';
    }
}
