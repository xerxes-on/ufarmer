<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\AgroCalendar\Enums\CalendarRunStatus;
use Modules\Core\Models\User;
use Modules\Harvest\Models\Harvest;

class CalendarRun extends Model
{
    use HasFactory;

    protected $table = 'agro_calendar_runs';

    protected $guarded = [];

    protected $casts = [
        'started_on' => 'date',
        'reference_date' => 'date',
        'completed_on' => 'date',
        'location_payload' => 'array',
        'metadata' => 'array',
        'settings' => 'array',
        'farming_goal' => 'string',
        'status' => CalendarRunStatus::class,
        'progress' => 'integer',
        'stage_updated_at' => 'datetime',
        'stage_confidence' => 'decimal:2',
        'predicted_harvest_date' => 'date',
        'predicted_yield' => 'decimal:2',
        'yield_confidence' => 'decimal:2',
        'actual_harvest_date' => 'date',
        'actual_yield' => 'decimal:2',
        'accuracy_pct' => 'decimal:2',
        'compliance_metadata' => 'array',
        'last_audit_export_at' => 'datetime',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(\Modules\Crops\Models\Crop::class, 'crop_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areaCrop(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\AreaCrop::class, 'area_crop_id');
    }

    public function getCurrentDay(): int
    {
        if (! $this->started_on) {
            return 0;
        }

        return (int) $this->started_on->diffInDays(now());
    }

    public function getFarmingGoal(): string
    {
        $goal = $this->farming_goal
            ?? ($this->settings['farming_goal'] ?? null);

        return is_string($goal) && $goal !== '' ? $goal : 'balanced';
    }

    public function hasPrediction(): bool
    {
        return $this->predicted_harvest_date !== null || $this->predicted_yield !== null;
    }

    public function getDaysUntilHarvest(): ?int
    {
        if (! $this->predicted_harvest_date) {
            return null;
        }

        return now()->diffInDays($this->predicted_harvest_date, false);
    }

    public function updateBbchStage(string $stage, float $confidence): void
    {
        $this->update([
            'current_bbch_stage' => $stage,
            'stage_confidence' => $confidence,
            'stage_updated_at' => now(),
        ]);
    }

    public function accuracyFactors(): HasMany
    {
        return $this->hasMany(CalendarAccuracyFactor::class, 'calendar_run_id')->orderBy('created_at');
    }

    public function harvest(): HasOne
    {
        return $this->hasOne(Harvest::class, 'calendar_run_id');
    }
}
