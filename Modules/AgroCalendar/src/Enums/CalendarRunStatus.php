<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

enum CalendarRunStatus: string
{
    case INITIALIZING = 'initializing';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case COMPLETED_WITH_WARNINGS = 'completed_warnings';
    case FAILED = 'failed';

    public function isInitializing(): bool
    {
        return $this === self::INITIALIZING;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isCompletedWithWarnings(): bool
    {
        return $this === self::COMPLETED_WITH_WARNINGS;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isLoading(): bool
    {
        return $this === self::INITIALIZING || $this === self::PENDING || $this === self::PROCESSING;
    }
}
