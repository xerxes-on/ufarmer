<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport\Worker;

use Illuminate\Support\Facades\DB;
use Modules\JobsServices\Models\AiImportRow;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Support\OfferLocales;

final class ImportedOfferLocaleBackfill
{
    public function __construct(
        private readonly OfferLocaleTranslator $translator = new OfferLocaleTranslator,
    ) {}

    /**
     * @return array{candidates: int, translated: int, title_updates: int, description_updates: int}
     */
    public function execute(bool $apply = false, ?int $limit = null): array
    {
        $candidates = $this->candidates($limit);
        $stats = [
            'candidates' => count($candidates),
            'translated' => 0,
            'title_updates' => 0,
            'description_updates' => 0,
        ];

        if (! $apply || $candidates === []) {
            return $stats;
        }

        $rows = array_map(static fn (array $candidate): array => [
            'row_index' => $candidate['offer']->getKey(),
            'data' => $candidate['row']->data,
        ], $candidates);

        $translated = $this->translator->translateRows($rows)['rows'];
        $byOffer = collect($translated)->keyBy('row_index');

        foreach ($candidates as $candidate) {
            /** @var ServiceOffer $offer */
            $offer = $candidate['offer'];
            /** @var AiImportRow $row */
            $row = $candidate['row'];
            $localized = (array) data_get($byOffer->get($offer->getKey()), 'data', []);
            $changes = [];

            if ($candidate['title']) {
                $changes['title'] = $localized['title_translations'];
                $stats['title_updates']++;
            }

            if ($candidate['description']) {
                $changes['description'] = array_filter(
                    (array) ($localized['description_translations'] ?? []),
                    static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
                );
                $stats['description_updates']++;
            }

            DB::transaction(function () use ($offer, $row, $localized, $changes): void {
                if ($changes !== []) {
                    $offer->forceFill($changes)->save();
                }

                $data = (array) $row->data;
                $data['title_translations'] = $localized['title_translations'];
                $data['description_translations'] = $localized['description_translations'];
                $row->forceFill(['data' => $data])->save();
            });

            $stats['translated']++;
        }

        return $stats;
    }

    /**
     * @return array<int, array{offer: ServiceOffer, row: AiImportRow, title: bool, description: bool}>
     */
    private function candidates(?int $limit): array
    {
        $rows = AiImportRow::query()
            ->where('status', 'published')
            ->whereNotNull('data->service_offer_id')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (AiImportRow $row): mixed => $row->value('service_offer_id'));

        $candidates = [];

        foreach ($rows as $row) {
            $offerId = (int) $row->value('service_offer_id');
            $offer = ServiceOffer::find($offerId);

            if ($offer === null) {
                continue;
            }

            $titleTranslations = $offer->getTitleTranslations();
            $descriptionTranslations = $offer->getDescriptionTranslations();
            $title = $this->isUniform($titleTranslations)
                || $this->isIncomplete($titleTranslations)
                || $this->hasCyrillicUz($titleTranslations)
                || $this->hasUntranslatedRussian($titleTranslations, (array) $row->data);
            $description = $this->isUniform($descriptionTranslations)
                || ($this->hasAnyValue($descriptionTranslations) && $this->isIncomplete($descriptionTranslations))
                || $this->hasCyrillicUz($descriptionTranslations);

            if (! $title && ! $description) {
                continue;
            }

            $candidates[] = compact('offer', 'row', 'title', 'description');

            if ($limit !== null && count($candidates) >= $limit) {
                break;
            }
        }

        return $candidates;
    }

    /**
     * Only replace a field whose existing non-empty locale values are all
     * identical. Once a human or an earlier backfill supplied distinct values,
     * it is curated and must not be overwritten.
     *
     * @param  array<string, mixed>  $translations
     */
    private function isUniform(array $translations): bool
    {
        $values = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): ?string => is_string($value) && trim($value) !== ''
                ? trim($value)
                : null,
            $translations,
        ))));

        return count($values) === 1;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function isIncomplete(array $translations): bool
    {
        foreach (OfferLocales::all() as $locale) {
            $value = $translations[$locale] ?? null;

            if (! is_string($value) || trim($value) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function hasCyrillicUz(array $translations): bool
    {
        $uz = is_string($translations['uz'] ?? null) ? trim($translations['uz']) : '';

        return $uz !== ''
            && preg_match('/\p{Cyrillic}/u', $uz) === 1;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function hasAnyValue(array $translations): bool
    {
        foreach ($translations as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $data
     */
    private function hasUntranslatedRussian(array $translations, array $data): bool
    {
        $sourceLanguage = strtolower((string) ($data['language'] ?? ''));
        $sourceTitle = is_string($data['title'] ?? null) ? trim($data['title']) : '';
        $ru = is_string($translations['ru'] ?? null) ? trim($translations['ru']) : '';

        return $sourceLanguage === 'uz'
            && $sourceTitle !== ''
            && $ru === $sourceTitle
            && preg_match('/[ЎўҚқҒғҲҳ]/u', $sourceTitle) === 1;
    }
}
