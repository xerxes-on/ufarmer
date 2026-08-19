<?php

declare(strict_types=1);

namespace Modules\Agronom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AgroCalendar\Models\CalendarRun;
use Modules\Agronom\Enums\RequestCalendarRunStatus;

class RequestCalendarRun extends Model
{
    protected $table = 'agronom_request_calendar_runs';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RequestCalendarRunStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * @return BelongsTo<CalendarRun, $this>
     */
    public function calendarRun(): BelongsTo
    {
        return $this->belongsTo(CalendarRun::class, 'calendar_run_id');
    }
}
