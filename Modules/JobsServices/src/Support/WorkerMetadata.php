<?php

declare(strict_types=1);

namespace Modules\JobsServices\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

final class WorkerMetadata
{
    private const string FIRST_SEEN_COLUMN = 'meta->activation->first_service_app_seen_at';

    private const string FIRST_AUTH_LOGIN_COLUMN = 'meta->activation->first_auth_login_at';

    private const array FIRST_SEEN_PATTERNS = [
        '____-__-__T__:__:__+__:__',
        '____-__-__T__:__:__-__:__',
        '____-__-__T__:__:__Z',
    ];

    public const string AI_IMPORT = 'ai_import';

    public static function isAiAdded(mixed $meta): bool
    {
        if (! is_array($meta)) {
            return false;
        }

        return data_get($meta, 'origin.type') === self::AI_IMPORT;
    }

    public static function firstServiceAppSeenAt(mixed $meta): ?CarbonImmutable
    {
        if (! is_array($meta)) {
            return null;
        }

        $value = data_get($meta, 'activation.first_service_app_seen_at');

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function firstAuthLoginAt(mixed $meta): ?CarbonImmutable
    {
        if (! is_array($meta)) {
            return null;
        }

        $value = data_get($meta, 'activation.first_auth_login_at');

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function hasAppActivity(mixed $meta): bool
    {
        return self::firstAuthLoginAt($meta) !== null
            || self::firstServiceAppSeenAt($meta) !== null;
    }

    public static function withAiImportOrigin(
        ?array $meta,
        int $batchId,
        int $rowId,
        DateTimeInterface|string $publishedAt,
    ): array {
        $meta ??= [];

        if (data_get($meta, 'origin.type') !== null) {
            return $meta;
        }

        data_set($meta, 'origin', [
            'type' => self::AI_IMPORT,
            'batch_id' => $batchId,
            'row_id' => $rowId,
            'published_at' => CarbonImmutable::parse($publishedAt)->toIso8601String(),
        ]);

        return $meta;
    }

    public static function whereFirstServiceAppSeenAtIsMissing(Builder $query): Builder
    {
        return $query->where(fn (Builder $activation): Builder => $activation
            ->whereNull(self::FIRST_SEEN_COLUMN)
            ->orWhere(function (Builder $invalid): void {
                foreach (self::FIRST_SEEN_PATTERNS as $pattern) {
                    $invalid->where(self::FIRST_SEEN_COLUMN, 'not like', $pattern);
                }
            }));
    }

    public static function whereFirstServiceAppSeenAtIsValid(Builder $query): Builder
    {
        return $query
            ->whereNotNull(self::FIRST_SEEN_COLUMN)
            ->where(function (Builder $valid): void {
                foreach (self::FIRST_SEEN_PATTERNS as $pattern) {
                    $valid->orWhere(self::FIRST_SEEN_COLUMN, 'like', $pattern);
                }
            });
    }

    public static function whereAppActivityIsMissing(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $service): Builder => self::whereTimestampIsMissing($service, self::FIRST_SEEN_COLUMN))
            ->where(fn (Builder $login): Builder => self::whereTimestampIsMissing($login, self::FIRST_AUTH_LOGIN_COLUMN));
    }

    public static function whereAppActivityIsValid(Builder $query): Builder
    {
        return $query->where(fn (Builder $activity): Builder => $activity
            ->where(fn (Builder $service): Builder => self::whereTimestampIsValid($service, self::FIRST_SEEN_COLUMN))
            ->orWhere(fn (Builder $login): Builder => self::whereTimestampIsValid($login, self::FIRST_AUTH_LOGIN_COLUMN)));
    }

    private static function whereTimestampIsMissing(Builder $query, string $column): Builder
    {
        return $query
            ->whereNull($column)
            ->orWhere(function (Builder $invalid) use ($column): void {
                foreach (self::FIRST_SEEN_PATTERNS as $pattern) {
                    $invalid->where($column, 'not like', $pattern);
                }
            });
    }

    private static function whereTimestampIsValid(Builder $query, string $column): Builder
    {
        return $query
            ->whereNotNull($column)
            ->where(function (Builder $valid) use ($column): void {
                foreach (self::FIRST_SEEN_PATTERNS as $pattern) {
                    $valid->orWhere($column, 'like', $pattern);
                }
            });
    }
}
