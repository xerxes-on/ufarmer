<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport\Worker;

use Modules\JobsServices\Support\OfferLocales;

/**
 * Strict JSON schema for service-worker extraction (UFARM-2644).
 *
 * Two rules OpenAI's `strict: true` mode imposes, both easy to trip over:
 * every declared property must also appear in `required`, and
 * `additionalProperties` must be false. "Optional" is therefore expressed as a
 * `["string", "null"]` union, not by omitting the key.
 *
 * Notably absent: ids, statuses, `is_active`. The model never picks a foreign
 * key — every category, region and city is resolved in PHP against the real
 * catalog, so a hallucinated id cannot reach the database.
 */
final class WorkerExtractionSchema
{
    public const COLLECTION_KEY = 'workers';

    /**
     * @return array<string, mixed>
     */
    public static function responseFormat(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'worker_extraction',
                'strict' => true,
                'schema' => self::schema(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [self::COLLECTION_KEY],
            'properties' => [
                self::COLLECTION_KEY => [
                    'type' => 'array',
                    'description' => 'One object per data row. Never a heading, header or blank row.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'group_label', 'company', 'person_name', 'phone', 'language',
                            'bio', 'experience_years', 'specializations', 'working_hours',
                            'region_name', 'city_name', 'address', 'latitude', 'longitude',
                            'category_name', 'service_title', 'service_description',
                            'price', 'price_text', 'area', 'currency',
                        ],
                        'properties' => self::rowProperties(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function rowProperties(): array
    {
        return [
            'group_label' => [
                'type' => ['string', 'null'],
                'description' => 'The section heading this row sits under, copied verbatim, e.g. "Услуги Дрона".',
            ],
            'company' => [
                'type' => ['string', 'null'],
                'description' => 'Фирма — company or trade name. Null when the cell is empty or "-".',
            ],
            'person_name' => [
                'type' => ['string', 'null'],
                'description' => 'Имя — the contact person. Null when only a company is given.',
            ],
            'phone' => [
                'type' => ['string', 'null'],
                'description' => 'Digits only: no spaces, dashes or +. Copy exactly the digits printed — never add or remove a country code.',
            ],
            'language' => [
                'type' => ['string', 'null'],
                'enum' => [...OfferLocales::all(), null],
                'description' => sprintf(
                    'Return the locale code that matches the language cell. Allowed locale codes: %s. Return null when it cannot be determined.',
                    implode(', ', OfferLocales::all()),
                ),
            ],
            'bio' => [
                'type' => ['string', 'null'],
                'description' => 'О Себе, copied as written.',
            ],
            'experience_years' => [
                'type' => ['number', 'null'],
                'description' => 'Опыт Работы in years. "5 йил" -> 5. Null when the cell is not a number.',
            ],
            'specializations' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Специализация split on commas and line breaks. Empty array when absent.',
            ],
            'working_hours' => [
                'type' => ['string', 'null'],
                'description' => 'Рабочее время copied verbatim, keeping line breaks.',
            ],
            'region_name' => [
                'type' => ['string', 'null'],
                'description' => 'The administrative region (вилоят / область / шаҳар), e.g. "Тошкент ш". Read the SWAPPED COLUMNS rule.',
            ],
            'city_name' => [
                'type' => ['string', 'null'],
                'description' => 'The district or city inside that region, e.g. "Чилонзор тумани".',
            ],
            'address' => [
                'type' => ['string', 'null'],
                'description' => 'Street address: anything with a street name, house number or landmark.',
            ],
            'latitude' => [
                'type' => ['number', 'null'],
                'description' => 'Decimal degrees. Strip trailing commas and spaces: "41.258827," -> 41.258827. Null if outside 37-46.',
            ],
            'longitude' => [
                'type' => ['number', 'null'],
                'description' => 'Decimal degrees. Null if outside 55-74.',
            ],
            'category_name' => [
                'type' => ['string', 'null'],
                'description' => 'Категория as written. When that cell is blank, use the section heading this row sits under.',
            ],
            'service_title' => [
                'type' => ['string', 'null'],
                'description' => 'Название — the service offered, copied as written.',
            ],
            'service_description' => [
                'type' => ['string', 'null'],
                'description' => 'Описание, copied as written.',
            ],
            'price' => [
                'type' => ['number', 'null'],
                'description' => 'A number ONLY when the row states one unambiguous price. Null for ranges, tiers and "По договору".',
            ],
            'price_text' => [
                'type' => ['string', 'null'],
                'description' => 'The price cell copied verbatim, always — including "По договору" and multi-line tiers.',
            ],
            'area' => [
                'type' => ['string', 'null'],
                'description' => 'Площадь copied verbatim, e.g. "Га", "Минимум 5 га".',
            ],
            'currency' => [
                'type' => ['string', 'null'],
                'enum' => ['UZS', 'USD', null],
            ],
        ];
    }
}
