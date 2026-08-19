<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AuthFarmers\Models\User;

class Job extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'price_fixed' => 'decimal:2',
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'property_size' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'deadline' => 'datetime',
        'fixed_time' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'views_count' => 'integer',
        'applications_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(JobMedia::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))";

        return $query->selectRaw("*, $haversine AS distance")
            ->whereRaw("$haversine < ?", [$radius])
            ->orderBy('distance');
    }

    public function getDisplayPriceAttribute()
    {
        if ($this->price_mode === 'fixed') {
            return number_format($this->price_fixed, 0, '', ' ').' '.$this->currency;
        }

        if ($this->price_max) {
            return number_format($this->price_min, 0, '', ' ').' - '.number_format($this->price_max, 0, '', ' ').' '.$this->currency;
        }

        return 'от '.number_format($this->price_min, 0, '', ' ').' '.$this->currency;
    }

    public function incrementViewsCount(): void
    {
        $this->increment('views_count');
    }
}
