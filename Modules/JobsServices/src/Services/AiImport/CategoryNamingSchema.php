<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use Modules\JobsServices\Support\OfferLocales;

/**
 * Strict JSON schema for naming a new service category (UFARM-2671).
 *
 * Every configured translation is required rather than optional: the resolver
 * indexes every one, so a category missing a language simply never matches a
 * sheet written in it — which would recreate the very gap this exists to
 * close.
 */
final class CategoryNamingSchema
{
    public const COLLECTION_KEY = 'categories';

    /**
     * @return array<string, mixed>
     */
    public static function responseFormat(): array
    {
        $properties = [
            'source' => [
                'type' => 'string',
                'description' => 'The label, copied character for character from the list you were given.',
            ],
        ];

        foreach (OfferLocales::all() as $locale) {
            $properties[$locale] = [
                'type' => 'string',
                'description' => sprintf('Short natural catalog name in locale %s.', $locale),
            ];
        }

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'category_naming',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [self::COLLECTION_KEY],
                    'properties' => [
                        self::COLLECTION_KEY => [
                            'type' => 'array',
                            'description' => 'Exactly one entry per label given.',
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['source', ...OfferLocales::all()],
                                'properties' => $properties,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
