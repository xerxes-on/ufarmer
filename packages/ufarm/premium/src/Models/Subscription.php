<?php

declare(strict_types=1);

namespace Ufarm\Premium\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Core\app\Models\User;
use Ufarm\Premium\Database\Factories\SubscriptionFactory;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Xerxes\AuthBridge\Models\Application;

class Subscription extends Pivot
{
    use HasFactory;

    public const TABLE = 'premium_plan_user';

    protected $table = self::TABLE;

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'premium_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
        'renewed_at',
        'application_id',
    ];

    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
        'canceled_at' => 'immutable_datetime',
        'renewed_at' => 'immutable_datetime',
        'status' => SubscriptionStatus::class,
    ];

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscription): void {
            $subscription->status ??= SubscriptionStatus::ACTIVE;
            $subscription->starts_at ??= CarbonImmutable::now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PremiumPlan::class, 'premium_plan_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE->value)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', CarbonImmutable::now());
            });
    }

    public function scopeForApplication($query, ?int $applicationId)
    {
        return $query->when($applicationId, fn ($q) => $q->where('application_id', $applicationId));
    }

    public function renew(CarbonInterface $newEndDate): void
    {
        $this->fill([
            'status' => SubscriptionStatus::ACTIVE,
            'renewed_at' => CarbonImmutable::now(),
            'ends_at' => CarbonImmutable::instance($newEndDate),
        ])->save();
    }

    public function markExpired(): void
    {
        if ($this->status === SubscriptionStatus::EXPIRED) {
            return;
        }

        $this->forceFill([
            'status' => SubscriptionStatus::EXPIRED,
        ])->save();
    }

    public function inactivate(): void
    {
        if ($this->status === SubscriptionStatus::INACTIVE) {
            return;
        }

        $this->forceFill([
            'status' => SubscriptionStatus::INACTIVE,
            'canceled_at' => CarbonImmutable::now(),
        ])->save();
    }
}
