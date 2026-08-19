<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class ParentCrop extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    public const string MEDIA_COLLECTION_IMAGE = 'parent_crop_image';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
    }

    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class);
    }

    public function activeCrops(): HasMany
    {
        return $this->hasMany(Crop::class)
            ->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE);

        if (! $media instanceof Media) {
            return null;
        }

        if ($media->disk === config('media-library.disk_name', 'public')) {
            return Storage::disk($media->disk)->url($media->getPathRelativeToRoot());
        }

        return $media->getUrl();
    }

    public function getLocalizedNameAttribute(): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return $this->getTranslation('name', $locale) ?: $this->getTranslation('name', $fallback) ?: null;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        $current = $this->getTranslation('description', $locale);

        if ($current !== null && $current !== '') {
            return $current;
        }

        $fallbackValue = $this->getTranslation('description', $fallback);

        return ($fallbackValue !== null && $fallbackValue !== '') ? $fallbackValue : null;
    }
}
