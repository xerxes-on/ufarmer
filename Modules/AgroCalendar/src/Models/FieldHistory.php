<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AgroCalendar\Enums\SoilFinishingStatus;
use Modules\Core\Models\Area;
use Modules\Crops\Models\Crop;

class FieldHistory extends Model
{
    protected $table = 'agro_field_histories';

    protected $guarded = [];

    protected $casts = [
        'season_year' => 'integer',
        'harvest_date' => 'date',
        'actual_yield' => 'decimal:2',
        'soil_finishing_status' => SoilFinishingStatus::class,
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(AgroUnit::class, 'yield_unit_id');
    }

    /**
     * Check if this was a fallow year (no crop planted)
     */
    public function isFallow(): bool
    {
        return $this->crop_id === null;
    }

    /**
     * Get yield with unit formatted
     */
    public function getFormattedYield(): ?string
    {
        if (! $this->actual_yield) {
            return null;
        }

        $unit = $this->yieldUnit?->symbol ?? '';

        return sprintf('%s %s', number_format((float) $this->actual_yield, 2), $unit);
    }
}
