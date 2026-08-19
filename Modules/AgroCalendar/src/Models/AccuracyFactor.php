<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AgroCalendar\Enums\AccuracyFactorAction;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class AccuracyFactor extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }

    protected $table = 'agro_accuracy_factors';

    protected $guarded = [];

    protected $casts = [
        'action' => AccuracyFactorAction::class,
        'boost_pct' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'name',
        'description',
    ];

    public const string MEDIA_COLLECTION_IMAGE = 'accuracy_factor_image';

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

    public function items(): HasMany
    {
        return $this->hasMany(AccuracyFactorItem::class, 'accuracy_factor_id')->orderBy('order');
    }

    public function calendarAccuracyFactors(): HasMany
    {
        return $this->hasMany(CalendarAccuracyFactor::class, 'accuracy_factor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
