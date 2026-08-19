<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class JobAnnouncement extends Model implements HasMedia
{
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    public const string MEDIA_COLLECTION_IMAGES = 'job_images';

    protected $table = 'job_announcements';

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'price' => 'decimal:2',
        'property_size' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'deadline' => 'datetime',
        'fixed_time' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'views_count' => 'integer',
        'applications_count' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGES)
            ->useDisk(config('media-library.disk_name', 'public'));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'executor_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_announcement_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get title for current locale.
     */
    public function getTitleAttribute(mixed $value): string
    {
        $titles = json_decode($value, true) ?? [];

        return $titles[app()->getLocale()] ?? $titles['en'] ?? '';
    }

    /**
     * Get description for current locale.
     */
    public function getDescriptionAttribute(mixed $value): string
    {
        $descriptions = json_decode($value, true) ?? [];

        return $descriptions[app()->getLocale()] ?? $descriptions['en'] ?? '';
    }

    /**
     * Get all translations for title.
     *
     * @return array<string, string>
     */
    public function getTitleTranslations(): array
    {
        return json_decode($this->attributes['title'] ?? '{}', true) ?? [];
    }

    /**
     * Get all translations for description.
     *
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return json_decode($this->attributes['description'] ?? '{}', true) ?? [];
    }
}
