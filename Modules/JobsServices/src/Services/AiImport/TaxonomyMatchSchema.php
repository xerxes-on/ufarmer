<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

/**
 * Strict JSON schema for taxonomy resolution (UFARM-2644).
 *
 * `target_id` is `["integer","null"]` on purpose: null is the correct answer
 * whenever no catalog entry means the same thing, and the prompt says so
 * explicitly. A schema that forced an integer would guarantee wrong matches.
 */
final class TaxonomyMatchSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function responseFormat(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'taxonomy_match',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['matches'],
                    'properties' => [
                        'matches' => [
                            'type' => 'array',
                            'description' => 'Exactly one entry per source name given.',
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['source_name', 'target_id', 'confidence'],
                                'properties' => [
                                    'source_name' => [
                                        'type' => 'string',
                                        'description' => 'Copied character for character from the list you were given.',
                                    ],
                                    'target_id' => [
                                        'type' => ['integer', 'null'],
                                        'description' => 'An id from the catalog list, or null when none means the same thing.',
                                    ],
                                    'confidence' => [
                                        'type' => 'number',
                                        'description' => '1.0 same concept in another language or spelling; 0.8 clearly the same family; below 0.5 when unsure.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
