<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AgroCalendar\Enums\NutrientEffectType;
use Modules\Crops\Models\Crop;
use Spatie\Translatable\HasTranslations;

class CropNutrientEffect extends Model
{
    use HasTranslations;

    protected $table = 'crop_nutrient_effects';

    protected $guarded = [];

    protected $casts = [
        'effect_type' => NutrientEffectType::class,
        'effect_percentage' => 'decimal:2',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'description',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForNutrient($query, string $nutrientCode)
    {
        return $query->where('nutrient_code', $nutrientCode);
    }

    public function scopeDepletingEffects($query)
    {
        return $query->where('effect_type', NutrientEffectType::DEPLETE->value);
    }

    public function scopeAddingEffects($query)
    {
        return $query->where('effect_type', NutrientEffectType::ADD->value);
    }
}
