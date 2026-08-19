<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarAccuracyFactor extends Model
{
    protected $table = 'agro_calendar_accuracy_factors';

    protected $guarded = [];

    protected $casts = [
        'boost_pct' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function calendarRun(): BelongsTo
    {
        return $this->belongsTo(CalendarRun::class, 'calendar_run_id');
    }

    public function accuracyFactor(): BelongsTo
    {
        return $this->belongsTo(AccuracyFactor::class, 'accuracy_factor_id');
    }
}
