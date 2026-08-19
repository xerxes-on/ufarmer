<?php

declare(strict_types=1);

namespace Modules\Exporter\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_unlimited
 * @property array<int> $region_ids
 * @property array<int> $crop_ids
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExporterProfile> $exporters
 */
class ExporterRole extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_unlimited',
        'region_ids',
        'crop_ids',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'region_ids' => 'array',
        'crop_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<ExporterProfile, self>
     */
    public function exporters(): HasMany
    {
        return $this->hasMany(ExporterProfile::class, 'exporter_role_id');
    }

    public function isExpired(): bool
    {
        if ($this->ends_at === null) {
            return false;
        }

        return $this->ends_at->isPast();
    }

    public function isNotStarted(): bool
    {
        if ($this->starts_at === null) {
            return false;
        }

        return $this->starts_at->isFuture();
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isNotStarted()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }
}
