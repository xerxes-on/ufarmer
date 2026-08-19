<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class City extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public array $translatable = ['name'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function getLocalizedNameAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->getTranslation('name', $locale) ?: $this->getTranslation('name', config('app.fallback_locale', 'en')) ?: null;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (): void {
            Cache::increment('regions_version');
        });

        static::updated(function (): void {
            Cache::increment('regions_version');
        });

        static::deleted(function (): void {
            Cache::increment('regions_version');
        });
    }
}
