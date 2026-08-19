<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Xerxes\BillingClient\Contracts\PaymentValidatorInterface;

/**
 * @property int $id
 * @property string $alias
 * @property string $name
 * @property string $validator_class
 * @property string $model_class
 * @property string $status_field
 * @property string $payment_status
 * @property string $paid_status
 * @property string $user_field
 * @property string $amount_field
 * @property string $currency_relation
 * @property string $currency_code_field
 * @property bool $is_active
 * @property array|null $config
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BillingServiceType extends Model
{
    protected $table = 'billing_service_types';

    protected $fillable = [
        'alias',
        'name',
        'validator_class',
        'model_class',
        'status_field',
        'payment_status',
        'paid_status',
        'user_field',
        'amount_field',
        'currency_relation',
        'currency_code_field',
        'is_active',
        'config',
    ];

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

    public function resolveValidator(): PaymentValidatorInterface
    {
        return app($this->validator_class);
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
        $status = $this->getEntityStatus($entity);
        $expectedStatus = $this->payment_status;

        if ($status instanceof BackedEnum) {
            return $status->value === $expectedStatus;
        }

        return (string) $status === $expectedStatus;
    }

    public function isPaidStatus(Model $entity): bool
    {
        $status = $this->getEntityStatus($entity);
        $expectedStatus = $this->paid_status;

        if ($status instanceof BackedEnum) {
            return $status->value === $expectedStatus;
        }

        return (string) $status === $expectedStatus;
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
}
