<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treatment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'dose_min' => 'decimal:4',
        'dose_max' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    public function pest(): BelongsTo
    {
        return $this->belongsTo(Pest::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function marketProduct(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'market_product_id');
    }

    public function problem(): ?Model
    {
        if (! $this->problem_type || ! $this->problem_id_poly) {
            return null;
        }

        return match ($this->problem_type) {
            'disease' => Disease::find($this->problem_id_poly),
            'pest' => Pest::find($this->problem_id_poly),
            'weed' => Weed::find($this->problem_id_poly),
            default => null,
        };
    }

    public function problemDisease(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'problem_id_poly')
            ->where($this->qualifyColumn('problem_type'), 'disease');
    }

    public function problemPest(): BelongsTo
    {
        return $this->belongsTo(Pest::class, 'problem_id_poly')
            ->where($this->qualifyColumn('problem_type'), 'pest');
    }

    public function problemWeed(): BelongsTo
    {
        return $this->belongsTo(Weed::class, 'problem_id_poly')
            ->where($this->qualifyColumn('problem_type'), 'weed');
    }

    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'treatment_crop');
    }

    public function cropsFromMarket(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'crop_treatment_market');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMarketProductTreatments($query)
    {
        return $query->whereNotNull('market_product_id');
    }

    public function isMarketProductTreatment(): bool
    {
        return $this->market_product_id !== null;
    }
}
