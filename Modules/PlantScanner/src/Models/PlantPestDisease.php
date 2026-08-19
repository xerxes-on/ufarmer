<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantPestDisease extends Model
{
    protected $table = 'plant_pests_diseases';

    protected $guarded = [];

    public function plantDetail(): BelongsTo
    {
        return $this->belongsTo(PlantDetail::class);
    }

    public function getName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"name_{$locale}"};
    }

    public function getDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"description_{$locale}"};
    }

    public function getTreatment(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"treatment_{$locale}"};
    }
}
