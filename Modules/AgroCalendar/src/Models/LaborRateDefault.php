<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Region;
use Spatie\Translatable\HasTranslations;

class LaborRateDefault extends Model
{
    use HasTranslations;

    protected $table = 'labor_rate_defaults';

    protected $guarded = [];

    protected $casts = [
        'workers_per_ha' => 'decimal:1',
        'daily_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'days_needed_per_ha' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'name',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
