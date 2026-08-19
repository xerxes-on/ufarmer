<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\Unit;
use Spatie\Translatable\HasTranslations;

class Param extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name'];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ParamOption::class);
    }

    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'crop_params')
            ->withPivot([
                'optimal_value',
                'min_value',
                'max_value',
                'param_option_id',
                'text_value',
                'boolean_value',
                'notes',
                'is_verified',
            ])
            ->withTimestamps();
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'region_params')
            ->withPivot('value', 'notes')
            ->withTimestamps();
    }

    public function scopeForCrops($query)
    {
        return $query->whereIn('applies_to', ['crop', 'both']);
    }

    public function scopeForRegions($query)
    {
        return $query->whereIn('applies_to', ['region', 'both']);
    }

    public function getLocalizedNameAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->getTranslation('name', $locale) ?: $this->getTranslation('name', config('app.fallback_locale', 'en')) ?: null;
    }
}
