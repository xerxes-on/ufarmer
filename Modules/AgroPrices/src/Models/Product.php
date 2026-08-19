<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

// use Modules\AgroPrices\Database\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $casts = [];

    public array $translatable = ['name'];

    public function dailyPrices(): HasMany
    {
        return $this->hasMany(DailyPrice::class);
    }

    // protected static function newFactory(): ProductFactory
    // {
    //     // return ProductFactory::new();
    // }
}
