<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\PlantScanner\Enums\PestDetailStatus;

class PestDetail extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => PestDetailStatus::class,
        'gallery_images' => 'array',
        'ai_enriched_data' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $pest): void {
            $pest->status = filled($pest->scientific_name)
                ? PestDetailStatus::Published
                : PestDetailStatus::Draft;
        });
    }

    public function getCommonName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"common_name_{$locale}"};
    }

    public function getDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"description_{$locale}"};
    }

    public function getAffectedCrops(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"affected_crops_{$locale}"};
    }

    public function getDamageSigns(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"damage_signs_{$locale}"};
    }

    public function getPrevention(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"prevention_{$locale}"};
    }

    public function getOrganicControl(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"organic_control_{$locale}"};
    }

    public function getChemicalControl(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"chemical_control_{$locale}"};
    }
}
