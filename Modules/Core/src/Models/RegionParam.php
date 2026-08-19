<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionParam extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:4',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function param(): BelongsTo
    {
        return $this->belongsTo(Param::class);
    }
}
