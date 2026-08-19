<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ServiceOffer extends Model implements HasMedia
{
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    protected $table = 'service_offers';

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'service_radius' => 'integer',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const string MEDIA_COLLECTION_IMAGES = 'service_images';

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MarketplaceServiceRequest::class, 'service_offer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get title for current locale
     */
    public function getTitleAttribute($value)
    {
        $titles = json_decode($value, true) ?? [];

        return $titles[app()->getLocale()] ?? $titles['en'] ?? '';
    }

    /**
     * Get description for current locale
     */
    public function getDescriptionAttribute($value)
    {
        $descriptions = json_decode($value, true) ?? [];

        return $descriptions[app()->getLocale()] ?? $descriptions['en'] ?? '';
    }

    /**
     * Get all translations for title
     */
    public function getTitleTranslations(): array
    {
        $title = json_decode($this->attributes['title'] ?? '{}', true);

        if (is_array($title)) {
            return $title;
        }

        return is_string($title) && $title !== ''
            ? [app()->getLocale() => $title]
            : [];
    }

    /**
     * Get all translations for description
     */
    public function getDescriptionTranslations(): array
    {
        return json_decode($this->attributes['description'] ?? '{}', true) ?? [];
    }
}
