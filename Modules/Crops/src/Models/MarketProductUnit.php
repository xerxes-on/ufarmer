<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketProductUnit extends Model
{
    protected $table = 'market_product_units';

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
        'allow_decimal' => 'boolean',
        'external_id' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(MarketProduct::class, 'unit_id');
    }

    public function getTranslatedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $this->name[$locale] ?? $this->name['uz'] ?? '';
    }
}
