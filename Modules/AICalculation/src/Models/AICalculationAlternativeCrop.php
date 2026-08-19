<?php

declare(strict_types=1);

namespace Modules\AICalculation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crops\Models\Crop;

class AICalculationAlternativeCrop extends Model
{
    protected $table = 'ai_calculation_alternative_crops';

    protected $guarded = [];

    protected $casts = [
        'suggested_crop_name_translations' => 'array',
        'reasoning' => 'array',
        'suitability_score' => 'decimal:2',
        'expected_yield' => 'decimal:2',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(AICalculationResult::class, 'ai_calculation_result_id');
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function getLocalizedNameAttribute(): ?string
    {
        $translations = $this->getAttribute('suggested_crop_name_translations');

        if (is_array($translations)) {
            return $translations[app()->getLocale()]
                ?? $translations[config('app.fallback_locale', 'en')]
                ?? reset($translations)
                ?: null;
        }

        return $this->crop?->localized_name ?? $this->getAttribute('suggested_crop_name');
    }

    public function getLocalizedReasoningAttribute(): mixed
    {
        $reasoning = $this->getAttribute('reasoning');

        if (! is_array($reasoning)) {
            return $reasoning;
        }

        return $reasoning[app()->getLocale()]
            ?? $reasoning[config('app.fallback_locale', 'en')]
            ?? reset($reasoning);
    }
}
