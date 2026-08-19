<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantDetail extends Model
{
    protected $guarded = [];

    protected $casts = [
        'temperature_min' => 'decimal:2',
        'temperature_max' => 'decimal:2',
        'soil_ph_min' => 'decimal:2',
        'soil_ph_max' => 'decimal:2',
        'humidity_min' => 'integer',
        'humidity_max' => 'integer',
        'gallery_images' => 'array',
        'category_extra_data' => 'array',
    ];

    public function scannedPlants(): HasMany
    {
        return $this->hasMany(ScannedPlant::class);
    }

    public function pestsAndDiseases(): HasMany
    {
        return $this->hasMany(PlantPestDisease::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PlantTag::class, 'plant_detail_tags');
    }

    public function getCommonName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"common_name_{$locale}"};
    }

    public function getFamily(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"family_{$locale}"};
    }

    public function getDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"description_{$locale}"};
    }
}
