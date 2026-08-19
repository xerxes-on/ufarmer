<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketProductCategory extends Model
{
    protected $table = 'market_product_categories';

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'external_id' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MarketProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MarketProductCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketProduct::class, 'category_id');
    }

    public function getTranslatedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $this->name[$locale] ?? $this->name['uz'] ?? '';
    }
}
