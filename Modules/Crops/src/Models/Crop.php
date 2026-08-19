<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Crop extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }
    use SoftDeletes;

    public const string MEDIA_COLLECTION_IMAGE = 'crop_image';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'baseline_yield' => 'decimal:2',
        'ky' => 'decimal:2',
        'temp_min' => 'decimal:2',
        'temp_max' => 'decimal:2',
        'temp_center' => 'decimal:2',
        'water_ph_min' => 'decimal:2',
        'water_ph_max' => 'decimal:2',
        'cycle_days' => 'integer',
        'recommended_temp_min' => 'float',
        'recommended_temp_max' => 'float',
    ];

    protected $appends = ['image_url', 'in_agrocalendar', 'temperature', 'external_price'];

    public array $translatable = ['name', 'description'];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (): void {
            Cache::increment('crops_version');
        });

        static::updated(function (): void {
            Cache::increment('crops_version');
        });

        static::deleted(function (): void {
            Cache::increment('crops_version');
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_crops')
            ->withTimestamps();
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'area_crops')
            ->withPivot(['area', 'date_started'])
            ->withTimestamps();
    }

    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'crop_disease')
            ->withTimestamps();
    }

    public function pests(): BelongsToMany
    {
        return $this->belongsToMany(Pest::class, 'crop_pest')
            ->withTimestamps();
    }

    public function weeds(): BelongsToMany
    {
        return $this->belongsToMany(Weed::class, 'crop_weed')
            ->withTimestamps();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CropPrice::class);
    }

    public function parentCrop(): BelongsTo
    {
        return $this->belongsTo(ParentCrop::class);
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    public function getTemperatureAttribute(): ?float
    {
        if ($this->recommended_temp_min === null || $this->recommended_temp_max === null) {
            return 22;
        }

        return ($this->recommended_temp_min + $this->recommended_temp_max) / 2;
    }

    public function getInAgrocalendarAttribute(mixed $value = null): bool
    {
        return is_bool($value) ? $value : (bool) ($this->attributes['in_agrocalendar'] ?? false);
    }

    public function getExternalPriceAttribute(): ?array
    {
        return $this->attributes['external_price'] ?? null;
    }

    public function getCalendarDetailsAttribute(): ?array
    {
        $template = $this->calendars()
            ->where('is_active', true)
            ->with(['tasks.definition.defaultImportance', 'tasks.statuses', 'tasks.tags'])
            ->orderByDesc('updated_at')
            ->first();

        if ($template === null) {
            return null;
        }

        $tasks = $template->tasks;

        return [
            'template_id' => $template->id,
            'name' => $template->getTranslations('name'),
            'description' => $template->getTranslations('description'),
            'duration_days' => $template->duration_days,
            'task_count' => $tasks->count(),
            'first_task_day' => $tasks->min('day_offset'),
            'last_task_day' => $tasks->max('day_offset'),
            'tasks' => $tasks->map(function ($task) {
                $definition = $task->definition;

                return [
                    'sequence' => $task->sequence,
                    'day_offset' => $task->day_offset,
                    'due_in_days' => $task->due_in_days,
                    'due_time' => $task->due_time,
                    'is_required' => $task->is_required,
                    'definition' => [
                        'code' => $definition->code,
                        'name' => $definition->getTranslations('name'),
                        'instructions' => $definition->getTranslations('instructions'),
                        'default_importance' => $definition->defaultImportance?->getTranslations('label'),
                    ],
                    'tags' => $task->tags->map(fn ($tag) => [
                        'code' => $tag->code,
                        'name' => $tag->getTranslations('name'),
                    ])->values()->all(),
                    'statuses' => $task->statuses->map(fn ($status) => [
                        'code' => $status->code,
                        'label' => $status->getTranslations('label'),
                        'is_terminal' => (bool) $status->is_terminal,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'metadata' => $template->metadata,
        ];
    }
}
