<?php

declare(strict_types=1);

namespace Modules\Harvest\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AgroCalendar\Models\CalendarRun;
use Modules\Core\Models\Area;
use Modules\Core\Models\AreaCrop;
use Modules\Core\Models\User;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\Currency;
use Modules\Crops\Models\Unit;

class Harvest extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'scheduled_harvest_date' => 'date',
        'estimated_amount' => 'decimal:2',
        'estimated_harvest_date' => 'date',
        'estimated_at' => 'datetime',
        'actual_amount' => 'decimal:2',
        'area_harvested' => 'decimal:2',
        'yield_per_hectare' => 'decimal:2',
        'harvested_at' => 'datetime',
        'is_early_harvest' => 'boolean',
        'quality_score' => 'decimal:2',
        'quality_attributes' => 'array',
        'temperature_celsius' => 'decimal:2',
        'humidity_percent' => 'decimal:2',
        'weather_data' => 'array',
        'selling_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'days_from_planting' => 'integer',
        'days_variance' => 'integer',
        'metadata' => 'array',
    ];

    public function areaCrop(): BelongsTo
    {
        return $this->belongsTo(AreaCrop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function calendarRun(): BelongsTo
    {
        return $this->belongsTo(CalendarRun::class);
    }

    public function qualityGrade(): BelongsTo
    {
        return $this->belongsTo(HarvestQualityGrade::class, 'quality_grade_id');
    }

    public function growthType(): BelongsTo
    {
        return $this->belongsTo(HarvestGrowthType::class, 'growth_type_id');
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(HarvestPriceType::class, 'price_type_id');
    }

    public function estimatedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'estimated_unit_id');
    }

    public function actualUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'actual_unit_id');
    }

    public function priceUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'price_unit_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HarvestPhoto::class)->ordered();
    }

    public function scopeHarvested(Builder $query): void
    {
        $query->whereNotNull('harvested_at');
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('harvested_at');
    }

    public function scopeWithEstimation(Builder $query): void
    {
        $query->whereNotNull('estimated_at');
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeForCrop(Builder $query, int $cropId): void
    {
        $query->where('crop_id', $cropId);
    }

    public function scopeForArea(Builder $query, int $areaId): void
    {
        $query->where('area_id', $areaId);
    }

    public function scopeInYear(Builder $query, int $year): void
    {
        $query->whereYear('harvested_at', $year);
    }

    public function isHarvested(): bool
    {
        return $this->harvested_at !== null;
    }

    public function hasEstimation(): bool
    {
        return $this->estimated_at !== null;
    }

    public function calculateYieldPerHectare(): ?float
    {
        if (! $this->area_harvested || $this->area_harvested <= 0) {
            return null;
        }

        if (! $this->actual_amount || $this->actual_amount <= 0) {
            return null;
        }

        $hectares = $this->area_harvested / 10_000;

        return round($this->actual_amount / $hectares, 2);
    }

    public function calculateTotalValue(): ?float
    {
        if (! $this->selling_price || ! $this->actual_amount) {
            return null;
        }

        return round($this->selling_price * $this->actual_amount, 2);
    }

    public function getEstimationVariance(): ?float
    {
        if (! $this->estimated_amount || ! $this->actual_amount) {
            return null;
        }

        return round($this->actual_amount - $this->estimated_amount, 2);
    }

    public function getEstimationVariancePercent(): ?float
    {
        if (! $this->estimated_amount || $this->estimated_amount <= 0 || ! $this->actual_amount) {
            return null;
        }

        $variance = $this->actual_amount - $this->estimated_amount;

        return round(($variance / $this->estimated_amount) * 100, 2);
    }
}
