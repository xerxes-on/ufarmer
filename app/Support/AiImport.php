<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Feature gate for AI-assisted imports (UFARM-2644).
 *
 * Two independent switches, both required: an explicit enable flag and a
 * present API key. An environment that has never been configured therefore
 * hides the feature entirely rather than exposing a button that fails at the
 * first request.
 *
 * Config-only by design. ufarm-marketplace's equivalent is backed by a
 * `Settings` model that does not exist in this repo, and a DB-backed toggle
 * would be a second source of truth for something deploy-time.
 */
final class AiImport
{
    public static function enabled(): bool
    {
        return (bool) config('services.openrouter.worker_import_enabled', false);
    }

    public static function configured(): bool
    {
        return filled(config('services.openrouter.key'));
    }

    /**
     * Enabled *and* able to reach OpenRouter. Checked at upload time and again
     * when the queued job starts, since the flag can flip in between.
     */
    public static function usable(): bool
    {
        return self::enabled() && self::configured();
    }

    public static function model(): string
    {
        return (string) config('services.openrouter.model', 'openai/gpt-4o-mini');
    }

    public static function maxUploadKb(): int
    {
        return (int) config('services.openrouter.worker_import_max_upload_kb', 10240);
    }

    /**
     * Application alias imported workers are attached to in ufarm-auth.
     */
    public static function workerApplicationAlias(): string
    {
        return (string) config('services.authbridge.worker_application_alias', 'in_service');
    }
}
