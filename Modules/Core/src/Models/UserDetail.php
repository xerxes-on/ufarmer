<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UserDetail extends Model implements HasMedia
{
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    public const string MEDIA_COLLECTION_PROFILE = 'user_profile';

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'birth_date' => 'date',
    ];

    protected $appends = ['image_url', 'profile_media_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_PROFILE)
            ->useDisk(config('filesystems.profile_disk', 'users'))
            ->singleFile();
    }

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('media')
            ? $this->media->where('collection_name', self::MEDIA_COLLECTION_PROFILE)->first()
            : $this->getFirstMedia(self::MEDIA_COLLECTION_PROFILE);

        if ($media instanceof Media) {
            return $media->getUrl();
        }

        if ($this->image) {
            return url('storage/'.$this->image);
        }

        return $this->getDefaultImageUrl();
    }

    public function getDefaultImageUrl(): string
    {
        $imageIndex = $this->user_id % 5;

        return url('storage/def/'.$imageIndex.'.jpeg');
    }

    public function getProfileMediaUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('media')
            ? $this->media->where('collection_name', self::MEDIA_COLLECTION_PROFILE)->first()
            : $this->getFirstMedia(self::MEDIA_COLLECTION_PROFILE);

        if ($media instanceof Media) {
            return $media->getUrl();
        }

        return null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
