<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlantTag extends Model
{
    protected $guarded = [];

    public function plantDetails(): BelongsToMany
    {
        return $this->belongsToMany(PlantDetail::class, 'plant_detail_tags');
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
}
