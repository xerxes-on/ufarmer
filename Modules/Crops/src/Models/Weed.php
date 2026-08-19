<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Weed extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    public const string MEDIA_COLLECTION_IMAGE = 'weed_image';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    public array $translatable = ['name', 'description', 'details'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
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

    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'crop_weed');
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'treatment_weed');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
