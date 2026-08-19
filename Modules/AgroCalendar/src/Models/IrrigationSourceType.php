<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class IrrigationSourceType extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'irrigation_source_types';

    protected $guarded = [];

    protected $casts = [
        'efficiency_percentage' => 'decimal:2',
        'carries_weed_seeds' => 'boolean',
        'carries_diseases' => 'boolean',
        'recommended_tasks' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'name',
        'description',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
