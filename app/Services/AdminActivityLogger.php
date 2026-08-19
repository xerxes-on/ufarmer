<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AdminActivityContext;
use DateTimeInterface;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

final readonly class AdminActivityLogger
{
    private const REDACTED = '[REDACTED]';

    private const IGNORED_TABLES = [
        'activity_log',
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'outbox_messages',
        'sessions',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ];

    private const IGNORED_UPDATE_ATTRIBUTES = [
        'created_at',
        'updated_at',
    ];

    public function __construct(private AdminActivityContext $context) {}

    /**
     * @throws JsonException
     */
    public function log(Model $model, string $event): void
    {
        if (! $this->context->isActive() || $this->shouldIgnore($model, $event)) {
            return;
        }

        $changes = $this->changesFor($model, $event);

        if ($changes === []) {
            return;
        }

        $causer = auth()->user();
        $timestamp = now();

        DB::table('activity_log')->insert([
            'log_name' => $model->getTable(),
            'description' => Str::headline(class_basename($model)).' '.Str::headline($event),
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'event' => $event,
            'causer_type' => $causer ? $causer::class : null,
            'causer_id' => $causer?->getAuthIdentifier(),
            'attribute_changes' => json_encode($changes, JSON_THROW_ON_ERROR),
            'properties' => json_encode($this->requestProperties(), JSON_THROW_ON_ERROR),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function shouldIgnore(Model $model, string $event): bool
    {
        if (in_array($model->getTable(), self::IGNORED_TABLES, true)) {
            return true;
        }

        return $event === 'deleted'
            && method_exists($model, 'isForceDeleting')
            && $model->isForceDeleting();
    }

    private function changesFor(Model $model, string $event): array
    {
        if ($event === 'created') {
            return ['attributes' => $this->sanitizeAttributes($model->getAttributes())];
        }

        if (in_array($event, ['deleted', 'forceDeleted'], true)) {
            return ['old' => $this->sanitizeAttributes($model->getAttributes())];
        }

        $changes = array_diff_key($model->getChanges(), array_flip(self::IGNORED_UPDATE_ATTRIBUTES));

        if ($changes === []) {
            return [];
        }

        if ($event === 'updated' && array_keys($changes) === ['deleted_at']) {
            return [];
        }

        if ($event === 'restored') {
            return ['attributes' => $this->sanitizeAttributes($changes)];
        }

        $old = [];

        foreach (array_keys($changes) as $attribute) {
            $old[$attribute] = $model->getOriginal($attribute);
        }

        return [
            'old' => $this->sanitizeAttributes($old),
            'attributes' => $this->sanitizeAttributes($changes),
        ];
    }

    private function sanitizeAttributes(array $attributes): array
    {
        $sanitized = [];

        foreach ($attributes as $attribute => $value) {
            $sanitized[$attribute] = $this->isSensitiveKey((string) $attribute)
                ? self::REDACTED
                : $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_array($value)) {
            return $this->sanitizeAttributes($value);
        }

        if (! is_string($value) || ! Str::of($value)->trim()->startsWith(['{', '['])) {
            return $value;
        }

        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }

        return is_array($decoded) ? $this->sanitizeAttributes($decoded) : $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return (bool) preg_match(
            '/(^|_)(password|passwd|remember_token|token|secret|api_key|access_key|private_key|authorization|otp|payload|session)(_|$)/i',
            $key,
        );
    }

    private function requestProperties(): array
    {
        $request = request();

        return array_filter([
            'panel' => Filament::getCurrentPanel()?->getId() ?? 'admin',
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
