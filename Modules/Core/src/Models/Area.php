<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AgroCalendar\Models\AreaSoilProfile;
use Modules\AgroCalendar\Models\IrrigationSourceType;
use Modules\Core\Services\Area\AreaMetricCalculator;
use Modules\Crops\Models\Crop;

class Area extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        AreaMetricCalculator::ATTRIBUTE_COORDINATES => 'array',
        AreaMetricCalculator::ATTRIBUTE_AREA => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waterSourceType(): BelongsTo
    {
        return $this->belongsTo(IrrigationSourceType::class, 'water_source_type_id');
    }

    public function soilProfiles(): HasMany
    {
        return $this->hasMany(AreaSoilProfile::class, 'area_id');
    }

    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, AreaMetricCalculator::PIVOT_TABLE)
            ->withPivot([
                AreaMetricCalculator::ATTRIBUTE_AREA,
                AreaMetricCalculator::PIVOT_DATE_STARTED,
                AreaMetricCalculator::PIVOT_ACTIVE,
                'expected_harvest_date',
                'harvested_at',
                'yield_amount',
                'yield_unit',
                'notes',
            ])
            ->withTimestamps();
    }

    public function activeCrops(): BelongsToMany
    {
        return $this->crops()->wherePivot(AreaMetricCalculator::PIVOT_ACTIVE, true);
    }

    public function inactiveCrops(): BelongsToMany
    {
        return $this->crops()->wherePivot(AreaMetricCalculator::PIVOT_ACTIVE, false);
    }

    public function areaCrops(): HasMany
    {
        return $this->hasMany(AreaCrop::class);
    }

    public function setCoordinatesAttribute($value): void
    {
        $this->attributes[AreaMetricCalculator::ATTRIBUTE_COORDINATES] = is_array($value)
            ? json_encode($value)
            : $value;

        if (is_array($value) && AreaMetricCalculator::isPolygon($value)) {
            $this->attributes[AreaMetricCalculator::ATTRIBUTE_AREA] = AreaMetricCalculator::calculatePolygonArea($value);
        }
    }

    public function getFormattedAreaAttribute(): string
    {
        $areaValue = (float) ($this->attributes[AreaMetricCalculator::ATTRIBUTE_AREA] ?? AreaMetricCalculator::DEFAULT_COORDINATE_VALUE);

        return AreaMetricCalculator::formatArea($areaValue);
    }

    public function getUsedArea(): float
    {
        return (float) AreaCrop::query()
            ->where('area_id', $this->id)
            ->where('active', true)
            ->whereNull('harvested_at')
            ->sum('area');
    }

    public function getAvailableArea(): float
    {
        $totalArea = (float) ($this->attributes[AreaMetricCalculator::ATTRIBUTE_AREA] ?? AreaMetricCalculator::DEFAULT_COORDINATE_VALUE);
        $usedArea = $this->getUsedArea();

        return max(0.0, $totalArea - $usedArea);
    }

    public function hasAvailableArea(float $requiredArea): bool
    {
        return $this->getAvailableArea() >= $requiredArea;
    }
}
