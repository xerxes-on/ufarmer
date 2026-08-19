<?php

declare(strict_types=1);

namespace Modules\Predictions\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Prediction Factor model for yield prediction weighting.
 * Uses the agro_accuracy_factors table (shared with AccuracyFactor).
 */
class PredictionFactor extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }

    protected $table = 'agro_accuracy_factors';

    protected $guarded = [];

    protected $casts = [
        'weight' => 'decimal:2',
        'boost_pct' => 'decimal:2',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'name',
        'description',
    ];

    public const string MEDIA_COLLECTION_IMAGE = 'prediction_factor_image';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
    }

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE);

        if ($media instanceof Media) {
            return $media->getUrl();
        }

        return null;
    }

    public function calendarFactors(): HasMany
    {
        return $this->hasMany(\Modules\AgroCalendar\Models\CalendarAccuracyFactor::class, 'accuracy_factor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getLocalizedNameAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->getTranslation('name', $locale) ?: $this->getTranslation('name', config('app.fallback_locale', 'en')) ?: null;
    }

    /**
     * Get the weight as a percentage display value.
     */
    public function getWeightPercentAttribute(): float
    {
        return (float) ($this->boost_pct ?? 0);
    }
}
