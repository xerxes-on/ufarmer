<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\General\Database\Factories\StoryFactory;

class Story extends Model
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
        'is_active' => 'boolean',
    ];

    // protected static function newFactory(): StoryFactory
    // {
    //     // return StoryFactory::new();
    // }
}
