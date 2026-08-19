<?php

declare(strict_types=1);

namespace Ufarm\Premium\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ufarm\Premium\Database\Factories\PremiumPlanFactory;
use Xerxes\AuthBridge\Models\Application;

class PremiumPlan extends Model
{
    use HasFactory;

    protected $table = 'premium_plans';

    protected $fillable = [
        'name',
        'price',
        'currency_id',
        'duration_days',
        'application_id',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
        'application_id' => 'integer',
        'currency_id' => 'integer',
    ];

    protected static function newFactory(): PremiumPlanFactory
    {
        return PremiumPlanFactory::new();
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'premium_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUniversal($query)
    {
        return $query->whereNull('application_id');
    }

    public function scopeForApplication($query, ?int $applicationId)
    {
        return $query->where(function ($q) use ($applicationId) {
            $q->whereNull('application_id')
                ->orWhere('application_id', $applicationId);
        });
    }

    public function isUniversal(): bool
    {
        return $this->application_id === null;
    }
}
