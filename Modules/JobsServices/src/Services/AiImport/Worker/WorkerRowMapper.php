<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport\Worker;

use Illuminate\Support\Str;
use Modules\Core\Enums\Currency;
use Modules\Core\Enums\PriceUnit;
use Modules\Core\Models\City;
use Modules\Core\Models\Region;
use Modules\JobsServices\Models\AiImportAlias;
use Modules\JobsServices\Models\ServiceCategory;
use Modules\JobsServices\Services\AiImport\FieldParser;
use Modules\JobsServices\Services\AiImport\TaxonomyResolver;
use Modules\JobsServices\Support\OfferLocales;

/**
 * Turns raw model output into staged, admin-reviewable worker rows (UFARM-2644).
 *
 * Two invariants:
 *
 *  - **No foreign key comes from the model.** Every category, region and city
 *    id is resolved here against the live catalog.
 *  - **Nothing is silently discarded.** A row that cannot be published keeps
 *    its values and carries a blocking error, so an admin fixes it on the
 *    review screen rather than wondering where it went.
 *
 * Errors are split in two. *Blocking* means the row cannot become a real
 * record (no usable phone, no category, no title). *Warnings* mean it can, but
 * a human should look — a fallback coordinate or a negotiable price is worth
 * surfacing, not worth stopping an import over. Deriving them is
 * WorkerRowValidator's job, so the review screen re-deriving them after an
 * edit cannot disagree with what the import first said.
 */
final class WorkerRowMapper
{
    /** Tashkent, used when a row resolves to no geography at all. */
    private const FALLBACK_LATITUDE = 41.2995;

    private const FALLBACK_LONGITUDE = 69.2401;

    private TaxonomyResolver $taxonomy;

    private WorkerRowValidator $validator;

    /** @var array<int, int> region id => its city ids */
    private array $cityIdsByRegion = [];

    /** @var array<int, Region> */
    private array $regions = [];

    /** @var array<int, City> */
    private array $cities = [];

    public function __construct()
    {
        $this->taxonomy = new TaxonomyResolver;
        $this->validator = new WorkerRowValidator;
        $this->loadCatalogs();
    }

    public function resolver(): TaxonomyResolver
    {
        return $this->taxonomy;
    }

    /**
     * Map extracted records into staged rows.
     *
     * Duplicates are folded on `phone + category + title`: the same worker
     * legitimately appears once per service they offer, so the phone alone is
     * not the identity of a row — only of the person.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>> ready for AiImportRow::create()
     */
    public function map(array $records): array
    {
        $rows = [];
        $seen = [];
        $index = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $row = $this->mapRow($record, $index);

            $fingerprint = $this->fingerprint($row);

            if ($fingerprint !== null && isset($seen[$fingerprint])) {
                continue;
            }

            if ($fingerprint !== null) {
                $seen[$fingerprint] = true;
            }

            $rows[] = $row;
            $index++;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function mapRow(array $record, int $index): array
    {
        $company = FieldParser::text($record['company'] ?? null, 255);
        $personName = FieldParser::text($record['person_name'] ?? null, 255);

        $phoneResult = FieldParser::phone(is_string($record['phone'] ?? null) ? $record['phone'] : null);

        $category = $this->resolveCategory($record);

        $title = FieldParser::text($record['service_title'] ?? null, 255)
            ?? FieldParser::text($record['category_name'] ?? null, 255)
            ?? FieldParser::text($record['group_label'] ?? null, 255);

        $data = [
            'company' => $company,
            'person_name' => $personName,
            'phone' => $phoneResult['phone'],
            'phone_raw' => FieldParser::text($record['phone'] ?? null, 64),
            'language' => $this->resolveLanguage($record['language'] ?? null),
            'bio' => FieldParser::text($record['bio'] ?? null, 1000),
            'experience_years' => FieldParser::experienceYears($record['experience_years'] ?? null),
            'specializations' => $this->resolveSpecializations($record, $company),
            'working_hours' => FieldParser::text($record['working_hours'] ?? null, 255),
            ...$this->resolveLocation($record),
            'category_id' => $category['id'],
            'category_name' => FieldParser::text($record['category_name'] ?? null, 255)
                ?? FieldParser::text($record['group_label'] ?? null, 255),
            'category_matched_by' => $category['matched_by'],
            'title' => $title,
            'description' => FieldParser::text($record['service_description'] ?? null, 2000),
            ...$this->resolvePrice($record),
            'area' => FieldParser::text($record['area'] ?? null, 255),
        ];

        return [
            'row_index' => $index,
            'label' => $personName ?? $company ?? $phoneResult['phone'],
            'group_label' => FieldParser::text($record['group_label'] ?? null, 255),
            // Identity of the person, not of the row — see map().
            'dedupe_key' => $phoneResult['phone'],
            'data' => $data,
            'raw' => $record,
            // Derived from the mapped values rather than alongside them, so the
            // review screen re-deriving them after an edit gets the same answer.
            'errors' => $this->validator->validate($data),
        ];
    }

    /**
     * The sheet fills Категория only on a block's first row, so a blank cell
     * inherits the block title — which is why the prompt asks for both.
     *
     * @param  array<string, mixed>  $record
     * @return array{id: int|null, matched_by: string|null}
     */
    private function resolveCategory(array $record): array
    {
        foreach ([$record['category_name'] ?? null, $record['group_label'] ?? null] as $candidate) {
            $name = FieldParser::text($candidate, 255);

            if ($name === null) {
                continue;
            }

            $match = $this->taxonomy->resolve(AiImportAlias::TAXONOMY_SERVICE_CATEGORY, $name);

            if ($match['id'] !== null) {
                return $match;
            }
        }

        return ['id' => null, 'matched_by' => null];
    }

    /**
     * Resolve region, city and coordinates.
     *
     * `service_offers.latitude`/`longitude` are NOT NULL, so a coordinate is
     * always produced: the sheet's own value when plausible, else the city
     * centre, else the region centre, else Tashkent. `coords_source` records
     * which — and the validator reads it back to raise the warning, since an
     * approximate pin an admin can drag beats a rejected row.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function resolveLocation(array $record): array
    {
        $regionName = FieldParser::text($record['region_name'] ?? null, 255);
        $cityName = FieldParser::text($record['city_name'] ?? null, 255);

        $region = $this->taxonomy->resolve(AiImportAlias::TAXONOMY_REGION, $regionName);

        $allowedCityIds = $region['id'] !== null
            ? ($this->cityIdsByRegion[$region['id']] ?? [])
            : null;

        // Scoped to the resolved region first: district names repeat across
        // regions, and the wrong "Chilonzor" is worse than none.
        $city = $this->taxonomy->resolve(AiImportAlias::TAXONOMY_CITY, $cityName, $allowedCityIds ?: null);

        if ($city['id'] === null && $cityName !== null) {
            $city = $this->taxonomy->resolve(AiImportAlias::TAXONOMY_CITY, $cityName);
        }

        // A city found without a region tells us the region.
        if ($region['id'] === null && $city['id'] !== null) {
            $regionId = $this->cities[$city['id']]->region_id ?? null;

            if ($regionId !== null) {
                $region = ['id' => (int) $regionId, 'matched_by' => 'exact'];
            }
        }

        $latitude = FieldParser::latitude($record['latitude'] ?? null);
        $longitude = FieldParser::longitude($record['longitude'] ?? null);
        $source = 'sheet';

        if ($latitude === null || $longitude === null) {
            [$latitude, $longitude, $source] = $this->fallbackCoordinates($region['id'], $city['id']);
        }

        return [
            'region_id' => $region['id'],
            'region_name' => $regionName,
            'city_id' => $city['id'],
            'city_name' => $cityName,
            'location_matched_by' => $region['matched_by'] ?? $city['matched_by'],
            'address' => FieldParser::text($record['address'] ?? null, 500),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'coords_source' => $source,
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: string}
     */
    private function fallbackCoordinates(?int $regionId, ?int $cityId): array
    {
        $city = $cityId !== null ? ($this->cities[$cityId] ?? null) : null;

        if ($city?->latitude !== null && $city?->longitude !== null) {
            return [(float) $city->latitude, (float) $city->longitude, 'city'];
        }

        $region = $regionId !== null ? ($this->regions[$regionId] ?? null) : null;

        if ($region?->center_lat !== null && $region?->center_lng !== null) {
            return [(float) $region->center_lat, (float) $region->center_lng, 'region'];
        }

        return [self::FALLBACK_LATITUDE, self::FALLBACK_LONGITUDE, 'fallback'];
    }

    /**
     * Resolve price, unit and currency.
     *
     * `service_offers.price` is NOT NULL and there is no "negotiable" column,
     * so a row priced "По договору" is stored as 0 and flagged: the publisher
     * carries the original wording into the offer description, and the warning
     * the validator raises off `price_negotiable` tells the admin why the
     * figure reads zero.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function resolvePrice(array $record): array
    {
        $raw = FieldParser::text($record['price_text'] ?? null, 255);
        $price = FieldParser::price($record['price'] ?? null) ?? FieldParser::price($raw);
        $negotiable = $price === null;

        $currency = is_string($record['currency'] ?? null)
            && Currency::tryFrom($record['currency']) !== null
                ? $record['currency']
                : Currency::UZS->value;

        return [
            'price' => $negotiable ? 0.0 : $price,
            'price_raw' => $raw,
            'price_negotiable' => $negotiable,
            'price_unit' => $this->resolvePriceUnit($record, $negotiable),
            'currency' => $currency,
        ];
    }

    /**
     * Infer the pricing unit from the area and price wording.
     *
     * Note PriceUnit has no "per service": a negotiable or unclassifiable
     * price becomes `per_project`, which is the closest honest fit.
     *
     * @param  array<string, mixed>  $record
     */
    private function resolvePriceUnit(array $record, bool $negotiable): string
    {
        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            is_string($record['area'] ?? null) ? $record['area'] : null,
            is_string($record['price_text'] ?? null) ? $record['price_text'] : null,
        ]))));

        if ($haystack !== '') {
            $units = [
                PriceUnit::PerAr->value => ['га', 'gektar', 'gektarga', 'ар', 'gа', 'ga'],
                PriceUnit::PerM2->value => ['м2', 'м²', 'm2', 'm²', 'кв.м', 'kv.m'],
                PriceUnit::PerHour->value => ['час', 'soat', 'соат', '/ч'],
                PriceUnit::PerKm->value => ['км', 'km', 'километ'],
                PriceUnit::PerDay->value => ['кун', 'день', 'дней', 'kun', 'сутк'],
                PriceUnit::PerItem->value => ['дона', 'шт', 'dona', 'штук'],
            ];

            foreach ($units as $unit => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($haystack, $needle)) {
                        return $unit;
                    }
                }
            }
        }

        return PriceUnit::PerProject->value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, string>
     */
    private function resolveSpecializations(array $record, ?string $company): array
    {
        $raw = $record['specializations'] ?? null;

        if (is_array($raw)) {
            $values = $raw;
        } elseif (is_string($raw)) {
            // A single cell listing several specialisations, comma- or
            // newline-separated as the sheet happens to write them.
            $values = preg_split('/[,;\n]+/', $raw) ?: [];
        } else {
            $values = [];
        }

        $values = array_values(array_filter(array_map(
            static fn (mixed $value): ?string => FieldParser::text(is_string($value) ? $value : null, 255),
            $values,
        )));

        // A worker with no stated specialisation still needs something
        // searchable; the company name is the best available signal.
        if ($values === [] && $company !== null) {
            $values = [$company];
        }

        return $values;
    }

    private function resolveLanguage(mixed $value): string
    {
        $value = is_string($value) ? strtolower(str_replace('_', '-', trim($value))) : null;

        return $value !== null && in_array($value, OfferLocales::all(), true)
            ? $value
            : OfferLocales::default();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fingerprint(array $row): ?string
    {
        $phone = $row['dedupe_key'] ?? null;

        if (! is_string($phone) || $phone === '') {
            // Without a phone there is no identity to fold on; keep every row
            // so the admin sees them all.
            return null;
        }

        return implode('|', [
            $phone,
            (string) ($row['data']['category_id'] ?? ''),
            Str::lower((string) ($row['data']['title'] ?? '')),
        ]);
    }

    private function loadCatalogs(): void
    {
        $regions = Region::query()->get();
        $cities = City::query()->get();

        $this->regions = $regions->keyBy('id')->all();
        $this->cities = $cities->keyBy('id')->all();

        foreach ($cities as $city) {
            $this->cityIdsByRegion[(int) $city->region_id][] = (int) $city->id;
        }

        $this->taxonomy->index(
            AiImportAlias::TAXONOMY_REGION,
            $regions,
            static fn (Region $region): array => $region->getTranslations('name'),
        );

        $this->taxonomy->index(
            AiImportAlias::TAXONOMY_CITY,
            $cities,
            static fn (City $city): array => $city->getTranslations('name'),
        );

        $this->taxonomy->index(
            AiImportAlias::TAXONOMY_SERVICE_CATEGORY,
            // Inactive categories are indexed too. An earlier import may have
            // created one for review that nobody has activated yet, and
            // skipping it would make the next import create the same category
            // again — a duplicate per run until someone noticed (UFARM-2671).
            // Matching one is safe: the row is flagged for review either way,
            // and publishing only makes it visible once an admin activates it.
            ServiceCategory::query()
                ->whereIn('applies_to', ['offer', 'both'])
                ->get(),
            static fn (ServiceCategory $category): array => $category->getTranslations('name'),
        );
    }
}
