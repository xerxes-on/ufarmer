<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\General\Database\Factories\ProductStatFactory;

class ProductStat extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array',
        'date' => 'date',
    ];

    // protected static function newFactory(): ProductStatFactory
    // {
    //     // return ProductStatFactory::new();
    // }
}
