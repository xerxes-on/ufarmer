<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Region;
use Spatie\Translatable\HasTranslations;

class AgroInputPrice extends Model
{
    use HasTranslations;

    protected $table = 'agro_input_prices';

    protected $guarded = [];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'season_year' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'name',
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
