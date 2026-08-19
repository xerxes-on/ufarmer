<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class ServiceCategory extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    public const string MEDIA_COLLECTION_IMAGE = 'category_image';

    public const string MEDIA_COLLECTION_BANNER = 'category_banner';

    public const string MEDIA_COLLECTION_ICON = 'category_icon';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'name' => 'array', // For JSON translations
        'applies_to' => 'string',
    ];

    protected $appends = ['image_url', 'banner_url', 'icon_url'];

    public array $translatable = ['name'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();

        $this->addMediaCollection(self::MEDIA_COLLECTION_BANNER)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();

        $this->addMediaCollection(self::MEDIA_COLLECTION_ICON)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
    }

    public function serviceOffers(): HasMany
    {
        return $this->hasMany(ServiceOffer::class, 'category_id');
    }

    public function jobAnnouncements(): HasMany
    {
        return $this->hasMany(JobAnnouncement::class, 'category_id');
    }

    // Keep the old method for backward compatibility but redirect to serviceOffers
    public function services(): HasMany
    {
        return $this->serviceOffers();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * `name` is a json column, and Postgres has no ordering operator for json,
     * so `orderBy('name')` is a hard SQL error rather than a slow query. Sort
     * on an extracted text translation instead, falling back across locales so
     * a category missing one translation still sorts somewhere sensible.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByRaw(
            "COALESCE(service_categories.name->>'uz', service_categories.name->>'ru', service_categories.name->>'en')"
        );
    }

    /**
     * Reassign sort_order for the given category ids to their index (1-based)
     * in $orderedIds, atomically. Goes through a negative, per-row-unique
     * staging value first: sort_order is protected by a unique constraint
     * (a partial index scoped to active, non-deleted rows for UFARM-2559),
     * and a plain single-statement swap can transiently collide with
     * another row's not-yet-updated value. Staging to negative values first
     * clears that collision risk for any constraint on the raw sort_order
     * column, regardless of its scoping predicate.
     *
     * @param  array<int, int>  $orderedIds
     */
    public static function reorderActive(array $orderedIds): void
    {
        if ($orderedIds === []) {
            return;
        }

        DB::transaction(function () use ($orderedIds): void {
            static::query()->whereIn('id', $orderedIds)->update(['sort_order' => DB::raw('-id')]);

            foreach ($orderedIds as $index => $id) {
                static::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
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

    public function getIconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_ICON);

        if (! $media instanceof Media) {
            return $this->icon;
        }

        if ($media->disk === config('media-library.disk_name', 'public')) {
            return Storage::disk($media->disk)->url($media->getPathRelativeToRoot());
        }

        return $media->getUrl();
    }

    public function getBannerUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_BANNER);

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
}
