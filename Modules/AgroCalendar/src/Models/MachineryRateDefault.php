<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Region;
use Spatie\Translatable\HasTranslations;

class MachineryRateDefault extends Model
{
    use HasTranslations;

    protected $table = 'machinery_rate_defaults';

    protected $guarded = [];

    protected $casts = [
        'rental_rate_per_ha' => 'decimal:2',
        'fuel_liters_per_ha' => 'decimal:2',
        'default_fuel_price' => 'decimal:2',
        'sessions_default' => 'integer',
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
