<?php

declare(strict_types=1);

namespace Modules\Agronom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Specialization extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function agronomDetails(): BelongsToMany
    {
        return $this->belongsToMany(
            AgronomDetail::class,
            'agronom_specializations',
            'specialization_id',
            'agronom_detail_id'
        )->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // `name` is a jsonb column; Postgres cannot order jsonb, so sort on an
    // extracted text translation. See ServiceCategory::scopeOrdered().
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE WHEN category = 'expertise' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->orderByRaw(
                "COALESCE(specializations.name->>'uz', specializations.name->>'ru', specializations.name->>'en')"
            );
    }
}
