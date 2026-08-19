<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class AccuracyFactorItem extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }

    protected $table = 'agro_accuracy_factor_items';

    protected $guarded = [];

    protected $casts = [
        'order' => 'integer',
    ];

    protected array $translatable = [
        'name',
        'description',
    ];

    public const string MEDIA_COLLECTION_ATTACHMENT = 'factor_item_attachment';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_ATTACHMENT)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->link) {
            return $this->link;
        }

        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_ATTACHMENT);

        if ($media instanceof Media) {
            return $media->getUrl();
        }

        return null;
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(AccuracyFactor::class, 'accuracy_factor_id');
    }
}
