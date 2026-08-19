<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Region extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public array $translatable = ['name'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function paramValues(): HasMany
    {
        return $this->hasMany(RegionParam::class);
    }

    public function params(): BelongsToMany
    {
        return $this->belongsToMany(Param::class, 'region_params')
            ->withPivot(['value', 'notes'])
            ->withTimestamps();
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
