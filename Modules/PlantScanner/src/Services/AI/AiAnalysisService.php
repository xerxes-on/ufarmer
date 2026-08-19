<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class AiAnalysisService
{
    private const PROVIDER = 'openrouter';

    /**
     * Analyse the supplied base64-encoded image and return structured plant data.
     *
     * @param  string  $base64Image  The image encoded as base64 (without data URI prefix).
     * @param  array<string, mixed>  $options  Optional overrides: language, model, timeout, max_retries.
     * @return array<string, mixed>
     */
    public function analyze(string $base64Image, array $options = []): array
    {
        $language = $options['language'] ?? config('plantscanner.ai.language', 'en');
        $model = $options['model'] ?? config('plantscanner.ai.model', 'openai/gpt-4.1-mini');
        $timeout = (int) ($options['timeout'] ?? config('plantscanner.ai.timeout', 60));
        $maxRetries = (int) ($options['max_retries'] ?? config('plantscanner.ai.max_retries', 3));

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($language),
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->buildUserPrompt($language),
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,'.$base64Image,
                            ],
                        ],
                    ],
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'PlantScanReport',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
            'temperature' => 0.2,
        ];

        $endpoint = config('services.openrouter.site_url');
        $apiKey = config('services.openrouter.api_key');

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout($timeout * $attempt)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => config('app.url', 'https://ufarm.app'),
                        'X-Title' => config('app.name', 'UFarm'),
                    ])
                    ->post($endpoint, $payload);

                if ($response->failed()) {
                    throw new RuntimeException('OpenRouter response error: '.$response->status().' - '.$response->body());
                }

                $content = data_get($response->json(), 'choices.0.message.content');
                if (! is_string($content) || trim($content) === '') {
                    throw new RuntimeException('OpenRouter returned empty content.');
                }

                $structured = $this->decodeJson($content);

                return [
                    'success' => true,
                    'analysis' => $this->flattenLocalized($structured['analysis_summary'] ?? null),
                    'structured_data' => $structured,
                    'provider' => self::PROVIDER,
                ];
            } catch (Throwable $exception) {
                Log::warning('Plant scan AI attempt failed', [
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                    'provider' => self::PROVIDER,
                ]);

                if ($attempt === $maxRetries) {
                    return [
                        'success' => false,
                        'error' => $exception->getMessage(),
                        'provider' => self::PROVIDER,
                    ];
                }

                // Exponential backoff: 250ms, 500ms, 1000ms, ...
                usleep((int) pow(2, $attempt - 1) * 250_000);
            }
        }

        return [
            'success' => false,
            'error' => 'AI analysis failed after all retry attempts.',
            'provider' => self::PROVIDER,
        ];
    }

    private function buildSystemPrompt(string $language): string
    {
        return implode("\n", [
            'You are a meticulous agronomist and botanist assisting farmers in Uzbekistan.',
            'Always respond with JSON that validates against the provided schema.',
            'For every human-readable string, produce translations for Uzbek (uz), Russian (ru), and English (en).',
            'Each translation must be natural and domain-appropriate. Avoid machine-translated tone.',
            'Keep recommendations factual and agriculture-focused. Make climate and seasonal advice specific to Uzbekistan whenever possible.',
            'If the subject is not a plant, set is_plant to false and leave plant-specific fields null where allowed while explaining the reasoning in analysis_summary.',
        ]);
    }

    private function buildUserPrompt(string $language): string
    {
        return 'Analyse the uploaded photo. Identify the plant, assess its condition, and provide practical care guidance. '
            .'Include fertiliser and micronutrient advice, seasonal tips for Uzbekistan, and likely diseases. '
            .'Every descriptive field must include uz, ru, and en keys with accurate translations. '
            .'Structure the reply strictly following the JSON schema.';
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'PlantScanReport',
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'analysis_summary',
                'is_plant',
                'name',
                'scientific_name',
                'family',
                'description',
                'current_condition',
                'potential_diseases',
                'temperature_range',
                'sunlight_requirements',
                'best_growing_seasons_uzbekistan',
                'mineral_needs',
                'vitamins',
                'recommended_care',
                'recommended_tags',
                'reference_images',
                'additional_notes',
            ],
            'properties' => [
                'analysis_summary' => $this->localizedSchema('Concise narrative summary of findings and key recommendations.'),
                'is_plant' => [
                    'type' => 'boolean',
                    'description' => 'Set to true when the subject is a plant.',
                ],
                'name' => $this->localizedSchema('Common name of the plant.', allowNull: true),
                'scientific_name' => [
                    'type' => ['string', 'null'],
                    'description' => 'Scientific (Latin binomial) name of the plant.',
                ],
                'family' => $this->localizedSchema('Botanical family.', allowNull: true),
                'description' => $this->localizedSchema('Short description highlighting notable traits.', allowNull: true),
                'current_condition' => $this->localizedSchema('Assessment of health, stress, or visible issues.', allowNull: true),
                'potential_diseases' => [
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'likelihood', 'notes'],
                        'properties' => [
                            'name' => $this->localizedSchema(),
                            'likelihood' => $this->localizedSchema(),
                            'notes' => $this->localizedSchema(),
                        ],
                    ],
                    'description' => 'Likely diseases or pests with notes on detection.',
                ],
                'temperature_range' => [
                    'type' => ['object', 'null'],
                    'required' => ['min_celsius', 'max_celsius', 'notes'],
                    'additionalProperties' => false,
                    'properties' => [
                        'min_celsius' => ['type' => ['number', 'null']],
                        'max_celsius' => ['type' => ['number', 'null']],
                        'notes' => $this->localizedSchema(),
                    ],
                ],
                'sunlight_requirements' => $this->localizedSchema('Light exposure needs (hours, intensity, placement).', allowNull: true),
                'best_growing_seasons_uzbekistan' => [
                    'type' => ['array', 'null'],
                    'items' => $this->localizedSchema(),
                    'minItems' => 1,
                    'description' => 'Ideal sowing/harvest windows anchored to Uzbekistan seasons.',
                ],
                'mineral_needs' => [
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'purpose', 'application_guidance'],
                        'properties' => [
                            'name' => $this->localizedSchema(),
                            'purpose' => $this->localizedSchema(),
                            'application_guidance' => $this->localizedSchema(),
                        ],
                    ],
                    'description' => 'Macro and micro nutrient requirements with fertiliser suggestions.',
                ],
                'vitamins' => [
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'benefits'],
                        'properties' => [
                            'name' => $this->localizedSchema(),
                            'benefits' => $this->localizedSchema(),
                        ],
                    ],
                    'description' => 'Key vitamins or phytonutrients the plant contains.',
                ],
                'recommended_care' => [
                    'type' => ['object', 'null'],
                    'additionalProperties' => false,
                    'required' => ['watering', 'soil', 'humidity', 'pruning', 'notes'],
                    'properties' => [
                        'watering' => $this->localizedSchema(),
                        'soil' => $this->localizedSchema(),
                        'humidity' => $this->localizedSchema(),
                        'pruning' => $this->localizedSchema(),
                        'notes' => $this->localizedSchema(),
                    ],
                    'description' => 'Core care guidelines summarised for quick action.',
                ],
                'recommended_tags' => [
                    'type' => ['array', 'null'],
                    'items' => $this->localizedSchema(),
                    'description' => 'Search or filter tags describing the plant.',
                ],
                'reference_images' => [
                    'type' => ['array', 'null'],
                    'items' => [
                        'type' => 'string',
                        'format' => 'uri',
                    ],
                    'minItems' => 3,
                    'description' => 'Helpful external photo URLs for comparison.',
                ],
                'additional_notes' => $this->localizedSchema('Any other context the farmer should know.', allowNull: true),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to decode structured AI response: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param  array<mixed>|string|null  $localized
     */
    private function flattenLocalized($localized): string
    {
        if (! is_array($localized)) {
            return is_string($localized) ? $localized : '';
        }

        $uz = $localized['uz'] ?? null;
        $ru = $localized['ru'] ?? null;
        $en = $localized['en'] ?? null;

        if (is_string($uz) && is_string($ru) && is_string($en)) {
            return 'uz: '.$uz.PHP_EOL.'ru: '.$ru.PHP_EOL.'en: '.$en;
        }

        try {
            return json_encode($localized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('Failed to flatten localized text', [
                'error' => $exception->getMessage(),
                'value' => $localized,
            ]);

            return '';
        }
    }

    private function localizedSchema(string $description = '', bool $allowNull = false): array
    {
        $objectSchema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['uz', 'ru', 'en'],
            'properties' => [
                'uz' => ['type' => 'string'],
                'ru' => ['type' => 'string'],
                'en' => ['type' => 'string'],
            ],
        ];

        if ($description !== '') {
            $objectSchema['description'] = $description;
        }

        if (! $allowNull) {
            return $objectSchema;
        }

        $schema = [
            'anyOf' => [
                $objectSchema,
                ['type' => 'null'],
            ],
        ];

        if ($description !== '') {
            $schema['description'] = $description;
        }

        return $schema;
    }
}
