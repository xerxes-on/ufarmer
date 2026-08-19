<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Fertilizer extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'image' => 'array',
        'metadata' => 'array',
    ];

    public array $translatable = ['name', 'description'];

    public function fertilizerCategory(): BelongsTo
    {
        return $this->belongsTo(FertilizerCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
