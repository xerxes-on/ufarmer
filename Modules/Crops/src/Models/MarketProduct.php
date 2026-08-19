<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketProduct extends Model
{
    protected $table = 'market_products';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'external_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MarketProductUnit::class, 'unit_id');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class, 'market_product_id');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->image) {
                    return null;
                }

                if (str_starts_with($this->image, 'http')) {
                    return $this->image;
                }

                return asset('storage/'.$this->image);
            }
        );
    }
}
