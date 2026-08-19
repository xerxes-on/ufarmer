<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Crops\Models\Crop;

class AreaCrop extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'area' => 'decimal:2',
        'date_started' => 'date',
        'active' => 'boolean',
        'harvested_at' => 'datetime',
        'expected_harvest_date' => 'date',
        'yield_amount' => 'decimal:2',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeInactive(Builder $query): void
    {
        $query->where('active', false);
    }

    public function toggleActive(): bool
    {
        $this->active = ! $this->active;

        return $this->save();
    }

    public function activate(): bool
    {
        $this->active = true;

        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->active = false;

        return $this->save();
    }

    public function isHarvested(): bool
    {
        return $this->harvested_at !== null;
    }

    public function harvest(?float $yieldAmount = null, ?string $yieldUnit = null, ?string $notes = null): bool
    {
        $this->harvested_at = now();
        $this->yield_amount = $yieldAmount;
        $this->yield_unit = $yieldUnit;
        $this->notes = $notes;
        $this->active = false;

        return $this->save();
    }

    public function scopeHarvested(Builder $query): void
    {
        $query->whereNotNull('harvested_at');
    }

    public function scopeNotHarvested(Builder $query): void
    {
        $query->whereNull('harvested_at');
    }
}
