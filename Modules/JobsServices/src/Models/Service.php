<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AuthFarmers\Models\User;
use Modules\Core\Enums\PriceUnit;

class Service extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'service_radius' => 'decimal:2',
        'availability' => 'array',
        'rating' => 'decimal:2',
        'views_count' => 'integer',
        'requests_count' => 'integer',
        'reviews_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ServiceMedia::class);
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

    public function getDisplayPriceAttribute(): string
    {
        $priceUnit = PriceUnit::tryFrom($this->price_unit);
        $unit = $priceUnit?->translatedLabel() ?? '/'.$this->price_unit;

        return number_format($this->price, 0, '', ' ').' '.$this->currency.$unit;
    }

    public function incrementViewsCount(): void
    {
        $this->increment('views_count');
    }

    public function incrementRequestsCount(): void
    {
        $this->increment('requests_count');
    }
}
