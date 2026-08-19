<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AgroPrices\Enums\PriceStatus;

// use Modules\AgroPrices\Database\Factories\DailyPriceFactory;

class DailyPrice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getStatusEnumAttribute(): PriceStatus
    {
        return PriceStatus::from($this->status);
    }

    //     protected static function newFactory(): DailyPriceFactory
    //     {
    //          return DailyPriceFactory::new();
    //     }
}
