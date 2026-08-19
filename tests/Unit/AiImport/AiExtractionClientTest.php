<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Illuminate\Support\Facades\Http;
use Modules\JobsServices\Services\AiImport\AiExtractionClient;
use Modules\JobsServices\Services\AiImport\ExtractionFailedException;
use Modules\JobsServices\Services\AiImport\ExtractionResult;
use Tests\TestCase;

/**
 * Transport behaviour for the OpenRouter client (UFARM-2644).
 */
class AiExtractionClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.model' => 'openai/gpt-4o-mini',
            'services.openrouter.fallback_models' => ['openai/gpt-5.2'],
            'services.openrouter.timeout' => 120,
            'services.openrouter.connect_timeout' => 10,
        ]);
    }

    public function test_it_extracts_records_and_records_usage(): void
    {
        $this->fakeContent(
            ['workers' => [['company' => 'AGRO'], ['company' => 'DRONE']]],
            ['prompt_tokens' => 120, 'completion_tokens' => 40, 'cost' => 0.0012],
        );

        $result = (new AiExtractionClient)->extract([], $this->schema(), 'workers');

        $this->assertSame(2, $result->count());
        $this->assertSame('openai/gpt-4o-mini', $result->model);
        $this->assertSame(120, $result->promptTokens);
        $this->assertSame(0.0012, $result->cost);
    }

    public function test_the_request_is_deterministic_and_routes_to_fallbacks(): void
    {
        $this->fakeContent(['workers' => []]);

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            // Creativity in an extraction means invented workers.
            return $body['temperature'] === 0
                && $body['route'] === 'fallback'
                && $body['models'] === ['openai/gpt-4o-mini', 'openai/gpt-5.2']
                && isset($body['response_format']['json_schema']);
        });
    }

    public function test_non_array_rows_are_dropped_rather_than_reaching_the_mapper(): void
    {
        $this->fakeContent(['workers' => [['company' => 'AGRO'], 'a bare string']]);

        $this->assertSame(1, (new AiExtractionClient)->extract([], $this->schema(), 'workers')->count());
    }

    public function test_a_refusal_is_surfaced_with_its_reason(): void
    {
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => '', 'refusal' => 'I cannot comply']]],
        ])]);

        $this->expectException(ExtractionFailedException::class);
        $this->expectExceptionMessageMatches('/model refused: I cannot comply/');

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');
    }

    public function test_a_provider_error_carries_its_body_for_diagnosis(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $this->expectException(ExtractionFailedException::class);
        $this->expectExceptionMessageMatches('/HTTP 429 rate limited/');

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');
    }

    public function test_prose_instead_of_json_is_an_error(): void
    {
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Sorry, here is some prose.']]],
        ])]);

        $this->expectException(ExtractionFailedException::class);
        $this->expectExceptionMessageMatches('/not valid JSON/');

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');
    }

    public function test_a_missing_collection_key_is_an_error(): void
    {
        $this->fakeContent(['something_else' => []]);

        $this->expectException(ExtractionFailedException::class);
        $this->expectExceptionMessageMatches('/no "workers" array/');

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');
    }

    public function test_a_missing_key_fails_before_any_request_is_made(): void
    {
        config(['services.openrouter.key' => '']);
        Http::fake();

        try {
            (new AiExtractionClient)->extract([], $this->schema(), 'workers');
            $this->fail('expected ExtractionFailedException');
        } catch (ExtractionFailedException) {
            Http::assertNothingSent();
        }
    }

    public function test_a_truncated_response_is_an_error_even_though_its_json_parses(): void
    {
        // The whole point: a `length` finish still decodes cleanly, just with
        // records missing off the end. Nothing downstream could tell.
        $this->fakeContent(
            ['workers' => [['company' => 'AGRO']]],
            ['completion_tokens' => 6684],
            finishReason: 'length',
        );

        $this->expectException(ExtractionFailedException::class);
        $this->expectExceptionMessageMatches('/ran out of output space after 6684/');

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');
    }

    public function test_a_provider_that_only_reports_a_native_finish_reason_is_still_caught(): void
    {
        Http::fake(['*' => Http::response([
            'choices' => [[
                'native_finish_reason' => 'length',
                'message' => ['content' => json_encode(['workers' => []])],
            ]],
        ])]);

        $this->expectException(ExtractionFailedException::class);
        $this->expectExceptionMessageMatches('/ran out of output space/');

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');
    }

    public function test_the_output_ceiling_is_sent_only_when_configured(): void
    {
        config(['services.openrouter.max_output_tokens' => 16000]);
        $this->fakeContent(['workers' => []]);

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');

        Http::assertSent(fn ($request): bool => $request->data()['max_tokens'] === 16000);

        config(['services.openrouter.max_output_tokens' => 0]);
        $this->fakeContent(['workers' => []]);

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');

        Http::assertSent(fn ($request): bool => ! isset($request->data()['max_tokens']));
    }

    public function test_a_config_cached_before_the_ceiling_existed_still_sends_one(): void
    {
        // A null key is what a stale `config:cache` yields, and config()'s
        // default does not apply to a key that is present-but-null. Left as
        // `(int) config(..., 0)` that silently dropped the ceiling entirely.
        config(['services.openrouter.max_output_tokens' => null]);
        $this->fakeContent(['workers' => []]);

        (new AiExtractionClient)->extract([], $this->schema(), 'workers');

        Http::assertSent(fn ($request): bool => $request->data()['max_tokens'] === 16000);
    }

    public function test_merging_sums_usage_across_chunks(): void
    {
        $first = new ExtractionResult([['a' => 1]], 'model-a', 100, 20, 0.001);
        $second = new ExtractionResult([['b' => 2]], 'model-b', 50, 10, 0.0005);

        $merged = $first->merge($second);

        $this->assertSame(2, $merged->count());
        $this->assertSame(150, $merged->promptTokens);
        $this->assertSame(30, $merged->completionTokens);
        $this->assertSame(0.0015, $merged->cost);
        // The first model is the one that was actually requested.
        $this->assertSame('model-a', $merged->model);
    }

    public function test_merging_keeps_unreported_usage_null(): void
    {
        // "The provider reported no usage" and "usage was zero" are different
        // facts, and only the second should ever display as 0.
        $merged = (new ExtractionResult([['a' => 1]]))->merge(new ExtractionResult([['b' => 2]]));

        $this->assertNull($merged->promptTokens);
        $this->assertNull($merged->cost);
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $usage
     */
    private function fakeContent(array $content, array $usage = [], string $finishReason = 'stop'): void
    {
        Http::fake(['*' => Http::response([
            'model' => 'openai/gpt-4o-mini',
            'choices' => [[
                'finish_reason' => $finishReason,
                'message' => ['content' => json_encode($content)],
            ]],
            'usage' => $usage,
        ])]);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return ['type' => 'json_schema', 'json_schema' => ['name' => 't', 'strict' => true, 'schema' => []]];
    }
}
