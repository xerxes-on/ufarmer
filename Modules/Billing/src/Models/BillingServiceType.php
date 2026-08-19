<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\PaidService;

class BillingServiceType extends Model
{
    protected $table = 'billing_service_types';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function paidService(): BelongsTo
    {
        return $this->belongsTo(PaidService::class);
    }

    public function findEntity(int|string $entityId): ?Model
    {
        $modelClass = $this->model_class;

        return $modelClass::query()->find($entityId);
    }

    public function getEntityStatus(Model $entity): mixed
    {
        return $entity->{$this->status_field};
    }

    public function getEntityUserId(Model $entity): mixed
    {
        return $entity->{$this->user_field};
    }

    public function getEntityAmount(Model $entity): float
    {
        return (float) $entity->{$this->amount_field};
    }

    public function getEntityCurrency(Model $entity): ?string
    {
        $relation = $this->currency_relation;
        $codeField = $this->currency_code_field;

        if (! method_exists($entity, $relation)) {
            return null;
        }

        $currency = $entity->{$relation};

        if ($currency === null) {
            return null;
        }

        return $currency->{$codeField} ?? null;
    }

    public function isPaymentPendingStatus(Model $entity): bool
    {
        return $this->statusMatches($this->getEntityStatus($entity), $this->payment_status);
    }

    public function isPaidStatus(Model $entity): bool
    {
        return $this->statusMatches($this->getEntityStatus($entity), $this->paid_status);
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public static function findByAlias(string $alias): ?self
    {
        $cacheTtl = config('ufarm-billing-client.cache_ttl', 3600);

        if ($cacheTtl > 0) {
            return Cache::remember(
                "billing_service_type:{$alias}",
                $cacheTtl,
                fn () => static::query()
                    ->where('alias', $alias)
                    ->where('is_active', true)
                    ->first()
            );
        }

        return static::query()
            ->where('alias', $alias)
            ->where('is_active', true)
            ->first();
    }

    public static function clearCache(string $alias): void
    {
        Cache::forget("billing_service_type:{$alias}");
    }

    private function statusMatches(mixed $status, string $expectedStatus): bool
    {
        if ($status instanceof BackedEnum) {
            return $status->value === $expectedStatus;
        }

        return (string) $status === $expectedStatus;
    }
}
