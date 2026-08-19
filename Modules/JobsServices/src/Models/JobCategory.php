<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCategory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // `name` is a json column; Postgres cannot order json, so sort on an
    // extracted text translation. See ServiceCategory::scopeOrdered().
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByRaw(
            "COALESCE(job_categories.name->>'uz', job_categories.name->>'ru', job_categories.name->>'en')"
        );
    }
}
