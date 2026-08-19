<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Region;

class CropYieldEstimate extends Model
{
    protected $table = 'crop_yield_estimates';

    protected $guarded = [];

    protected $casts = [
        'yield_per_ha_min' => 'decimal:2',
        'yield_per_ha_avg' => 'decimal:2',
        'yield_per_ha_max' => 'decimal:2',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(\Modules\Crops\Models\Crop::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
