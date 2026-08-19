<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Illuminate\Support\Facades\Http;
use Modules\JobsServices\Services\AiImport\ExtractionFailedException;
use Modules\JobsServices\Services\AiImport\Worker\OfferLocaleTranslator;
use Tests\TestCase;

class OfferLocaleTranslatorTest extends TestCase
{
    public function test_it_uses_configured_locales_without_code_changes(): void
    {
        config([
            'app.api_locales' => ['uz', 'ru', 'en', 'kk'],
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.model' => 'test/model',
            'services.openrouter.translation_retry_model' => 'test/retry-model',
            'services.openrouter.fallback_models' => [],
            'services.openrouter.timeout' => 120,
            'services.openrouter.connect_timeout' => 10,
        ]);

        Http::fake(['*' => Http::response([
            'model' => 'test/model',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode(['translations' => [[
                    'row_id' => 7,
                    'title_uz' => 'Purkash',
                    'title_ru' => 'Опрыскивание',
                    'title_en' => 'Spraying',
                    'title_kk' => 'Бүрку',
                    'description_uz' => 'Narx kelishiladi',
                    'description_ru' => 'Цена договорная',
                    'description_en' => 'Price is negotiable',
                    'description_kk' => 'Бағасы келісімді',
                ]]])],
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'cost' => 0.001],
        ])]);

        $translated = (new OfferLocaleTranslator)->translateRows([[
            'row_index' => 7,
            'data' => [
                'title' => 'Дори сепиш',
                'language' => 'uz',
                'description' => null,
                'price_raw' => 'По договору',
                'price_negotiable' => true,
            ],
        ]]);

        $this->assertSame('Бүрку', $translated['rows'][0]['data']['title_translations']['kk']);
        $this->assertSame('Бағасы келісімді', $translated['rows'][0]['data']['description_translations']['kk']);

        Http::assertSent(function ($request): bool {
            $schema = $request->data()['response_format']['json_schema']['schema'];
            $properties = $schema['properties']['translations']['items']['properties'];
            $system = collect($request->data()['messages'])->firstWhere('role', 'system');
            $user = collect($request->data()['messages'])->firstWhere('role', 'user');

            return isset($properties['title_kk'], $properties['description_kk'])
                && str_contains((string) $system['content'], 'uz, ru, en, kk')
                && str_contains((string) $system['content'], 'uz must be Uzbek written in Latin script')
                && str_contains((string) $system['content'], 'ru must be Russian written in Cyrillic')
                && str_contains((string) $user['content'], '"description_required":true')
                && str_contains((string) $user['content'], '"source_language":"uz"');
        });
    }

    public function test_it_rejects_invalid_description_locales_when_description_inputs_exist(): void
    {
        config([
            'app.api_locales' => ['uz', 'ru', 'en'],
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.model' => 'test/model',
            'services.openrouter.translation_retry_model' => 'test/retry-model',
            'services.openrouter.fallback_models' => [],
        ]);

        Http::fake(['*' => Http::response([
            'model' => 'test/model',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode(['translations' => [[
                    'row_id' => 8,
                    'title_uz' => 'Texnika xizmatlari',
                    'title_ru' => 'Услуги техники',
                    'title_en' => 'Machinery services',
                    'description_uz' => 'По договору',
                    'description_ru' => 'Цена договорная',
                    'description_en' => 'Price is negotiable',
                ]]])],
            ]],
        ])]);

        $this->expectException(ExtractionFailedException::class);

        try {
            (new OfferLocaleTranslator)->translateRows([[
                'row_index' => 8,
                'data' => [
                    'title' => 'Услуги техники',
                    'description' => null,
                    'price_negotiable' => true,
                ],
            ]]);
        } finally {
            Http::assertSentCount(2);
            Http::assertSent(function ($request): bool {
                $systemMessages = collect($request->data()['messages'])
                    ->where('role', 'system')
                    ->pluck('content')
                    ->implode("\n");

                return str_contains($systemMessages, 'previous response failed locale validation')
                    && str_contains($systemMessages, 'Капельное орошение')
                    && $request->data()['model'] === 'test/retry-model';
            });
        }
    }
}
