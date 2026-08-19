<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use RuntimeException;

/**
 * An AI extraction could not be completed (UFARM-2644).
 *
 * The message reaches an admin on the review screen, so each constructor keeps
 * enough provider detail to diagnose the failure without opening the queue log.
 */
final class ExtractionFailedException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('OpenRouter is not configured: no API key is set.');
    }

    public static function transport(string $detail): self
    {
        return new self(sprintf('Could not reach OpenRouter: %s', $detail));
    }

    public static function badResponse(string $detail): self
    {
        return new self(sprintf('OpenRouter returned an unusable response: %s', $detail));
    }

    /**
     * The model ran out of output budget mid-answer.
     *
     * Worth its own constructor because the failure is otherwise invisible:
     * a truncated structured response can still parse as valid JSON, just with
     * records missing off the end, and the import would report success while
     * having quietly dropped rows. The remedy is a bigger `max_tokens` or
     * smaller chunks, so the message says so.
     */
    public static function truncated(?int $completionTokens): self
    {
        return new self(sprintf(
            'The model ran out of output space after %s completion tokens, so the response was cut short. Lower OPENROUTER_MAX_INPUT_CHARS or raise OPENROUTER_MAX_OUTPUT_TOKENS.',
            $completionTokens === null ? 'an unreported number of' : (string) $completionTokens,
        ));
    }

    public static function sourceUnreadable(string $detail): self
    {
        return new self(sprintf('The import source could not be read: %s', $detail));
    }
}
