<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use App\Support\AiImport;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * OpenRouter client for AI-assisted imports (UFARM-2644).
 *
 * Entity-agnostic: callers supply their own messages and JSON schema, so the
 * worker importer and any later one share this transport rather than each
 * growing a copy of the retry, fallback-routing and error-surfacing rules.
 *
 * Mirrors ufarm-marketplace's ProductExtractionClient, with the transport
 * factored into a single `send()` — that client duplicates it between its
 * extraction and taxonomy-resolution paths, and the two have to stay identical
 * to behave predictably.
 *
 * No retry loop here: retries are the queued job's business (`tries`/`backoff`),
 * and retrying a 120-second LLM call in-process would sit on a worker for
 * minutes while the queue already knows how to do this properly.
 */
class AiExtractionClient
{
    /**
     * Run a structured completion and decode the JSON object it returns.
     *
     * The schema is passed as a full `response_format` so callers can use
     * strict structured output; the model is then contractually unable to
     * return prose, and a parse failure means a genuine provider problem.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $responseFormat
     * @param  string  $collectionKey  top-level array key to pull records from
     *
     * @throws ExtractionFailedException
     */
    public function extract(
        array $messages,
        array $responseFormat,
        string $collectionKey,
        ?string $model = null,
    ): ExtractionResult {
        $body = $this->send($messages, $responseFormat, $model);
        $decoded = $this->decodeContent($body);

        $records = data_get($decoded, $collectionKey);

        if (! is_array($records)) {
            throw ExtractionFailedException::badResponse(
                sprintf('response had no "%s" array', $collectionKey)
            );
        }

        return new ExtractionResult(
            // Drop scalars defensively: `strict` schemas make them impossible,
            // but a malformed row must not reach the mapper as a string.
            records: array_values(array_filter($records, 'is_array')),
            model: $this->stringOrNull(data_get($body, 'model')),
            promptTokens: $this->intOrNull(data_get($body, 'usage.prompt_tokens')),
            completionTokens: $this->intOrNull(data_get($body, 'usage.completion_tokens')),
            cost: $this->floatOrNull(data_get($body, 'usage.cost')),
        );
    }

    /**
     * Run a structured completion and return the decoded object as-is.
     *
     * For calls whose payload is not a list of records — taxonomy resolution
     * returns a mapping. Shares `send()` with `extract()`, so the two cannot
     * drift on timeouts, headers or fallback routing.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $responseFormat
     * @return array{content: array<string, mixed>, model: ?string, prompt_tokens: ?int, completion_tokens: ?int, cost: ?float}
     *
     * @throws ExtractionFailedException
     */
    public function structured(array $messages, array $responseFormat, ?string $model = null): array
    {
        $body = $this->send($messages, $responseFormat, $model);

        return [
            'content' => $this->decodeContent($body),
            'model' => $this->stringOrNull(data_get($body, 'model')),
            'prompt_tokens' => $this->intOrNull(data_get($body, 'usage.prompt_tokens')),
            'completion_tokens' => $this->intOrNull(data_get($body, 'usage.completion_tokens')),
            'cost' => $this->floatOrNull(data_get($body, 'usage.cost')),
        ];
    }

    /**
     * The one place an HTTP request to OpenRouter is made.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $responseFormat
     * @return array<string, mixed>
     *
     * @throws ExtractionFailedException
     */
    private function send(array $messages, array $responseFormat, ?string $model): array
    {
        $key = (string) config('services.openrouter.key');

        if ($key === '') {
            throw ExtractionFailedException::notConfigured();
        }

        $model ??= AiImport::model();

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'response_format' => $responseFormat,
            // Deterministic extraction: creativity here means invented records.
            'temperature' => 0,
        ];

        // Only sent when configured, so a provider with a sane default is left
        // alone. Safe to set at all because a too-small value now fails loudly
        // — see the finish_reason check below.
        // Read the key rather than leaning on config()'s default: that default
        // only applies when the key is ABSENT, and a server on a cached config
        // built before this key existed reads null — which as `(int) null` was
        // 0, silently dropping the ceiling. An explicit 0 still means "leave it
        // to the provider" (UFARM-2671).
        $configured = config('services.openrouter.max_output_tokens');
        $maxOutputTokens = $configured === null ? 16000 : (int) $configured;

        if ($maxOutputTokens > 0) {
            $payload['max_tokens'] = $maxOutputTokens;
        }

        // OpenRouter-specific: try these models in order if the first is
        // unavailable or rate-limited, rather than failing the whole import.
        $fallbacks = array_values(array_filter(
            (array) config('services.openrouter.fallback_models'),
            static fn ($candidate): bool => $candidate !== $model && filled($candidate),
        ));

        if ($fallbacks !== []) {
            $payload['models'] = [$model, ...$fallbacks];
            $payload['route'] = 'fallback';
        }

        try {
            $response = Http::baseUrl((string) config('services.openrouter.base_url'))
                ->withToken($key)
                ->withHeaders(array_filter([
                    'HTTP-Referer' => config('services.openrouter.referer'),
                    'X-Title' => config('services.openrouter.title'),
                ]))
                ->timeout((int) config('services.openrouter.timeout'))
                ->connectTimeout((int) config('services.openrouter.connect_timeout'))
                ->asJson()
                ->post('/chat/completions', $payload);
        } catch (Throwable $e) {
            throw ExtractionFailedException::transport($e->getMessage());
        }

        if ($response->failed()) {
            throw ExtractionFailedException::transport(sprintf(
                'HTTP %d %s',
                $response->status(),
                // Provider error bodies are small; include one for diagnosis.
                mb_substr((string) data_get($response->json(), 'error.message', $response->body()), 0, 300),
            ));
        }

        $body = $response->json();

        $body = is_array($body) ? $body : [];

        $this->assertNotTruncated($body);

        return $body;
    }

    /**
     * Refuse a completion the model did not finish.
     *
     * This is the difference between an import that loses rows and one that
     * says so. A `length` finish means the model hit its output ceiling
     * mid-answer; under a strict schema the JSON can still decode, just short
     * of some records, so nothing downstream can tell a truncated extraction
     * from a complete one. Throwing here lets the queue retry and, failing
     * that, puts the reason on the batch where an admin reads it.
     *
     * Both keys are checked: OpenRouter normalises to `finish_reason`, but
     * some providers only populate `native_finish_reason`.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws ExtractionFailedException
     */
    private function assertNotTruncated(array $body): void
    {
        foreach (['finish_reason', 'native_finish_reason'] as $key) {
            if (data_get($body, 'choices.0.'.$key) === 'length') {
                throw ExtractionFailedException::truncated(
                    $this->intOrNull(data_get($body, 'usage.completion_tokens')),
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     *
     * @throws ExtractionFailedException
     */
    private function decodeContent(array $body): array
    {
        $content = data_get($body, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            // A refusal arrives in its own field rather than as content, and
            // reads far better to an admin than "empty response".
            $refusal = data_get($body, 'choices.0.message.refusal');

            throw ExtractionFailedException::badResponse(
                is_string($refusal) && $refusal !== ''
                    ? sprintf('model refused: %s', mb_substr($refusal, 0, 200))
                    : 'empty message content',
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw ExtractionFailedException::badResponse('message content was not valid JSON');
        }

        return $decoded;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
