<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Crops\Models\Crop;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// use Modules\General\Database\Factories\ArticleFactory;

class Article extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public const COLLECTION_ATTACHMENT = 'article_attachment';

    public const COLLECTION_ICON = 'article_icon';

    public const COLLECTION_PREVIEW = 'article_preview';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'crop_ids' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_PREVIEW)
            ->useDisk(config('media-library.disk_name'))
            ->singleFile();

        $this->addMediaCollection(self::COLLECTION_ICON)
            ->useDisk(config('media-library.disk_name'))
            ->singleFile();

        $this->addMediaCollection(self::COLLECTION_ATTACHMENT)
            ->useDisk(config('media-library.disk_name'))
            ->singleFile();
    }

    // Note: Crops are linked via crop_ids JSON field, not a pivot.

    /**
     * Get tags related to this article.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'article_article_tag');
    }

    /**
     * Derived crops list from the crop_ids field.
     */
    public function getCropsAttribute()
    {
        if (empty($this->crop_ids)) {
            return collect();
        }

        return Crop::whereIn('id', $this->crop_ids)->get();
    }

    /**
     * Scope articles that match any of the provided crop IDs.
     */
    public function scopeForCrops($query, iterable $cropIds)
    {
        $ids = collect($cropIds)
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return $query;
        }

        return $query->where(function ($q) use ($ids): void {
            foreach ($ids as $id) {
                $q->orWhereJsonContains('crop_ids', $id);
            }
        });
    }

    /**
     * Scope articles without a crop assignment.
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('crop_ids');
    }

    protected function previewImage(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->resolveMediaPath(self::COLLECTION_PREVIEW, $value),
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->resolveMediaPath(self::COLLECTION_ICON, $value),
        );
    }

    protected function attachment(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->resolveMediaPath(self::COLLECTION_ATTACHMENT, $value),
        );
    }

    private function resolveMediaPath(string $collection, ?string $fallback): ?string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media instanceof Media) {
            return $fallback;
        }

        $disk = $media->disk;
        $defaultDisk = config('media-library.disk_name', 'public');

        if ($disk === $defaultDisk) {
            return $media->getPathRelativeToRoot();
        }

        return $media->getUrl();
    }

    // protected static function newFactory(): ArticleFactory
    // {
    //     // return ArticleFactory::new();
    // }
}
