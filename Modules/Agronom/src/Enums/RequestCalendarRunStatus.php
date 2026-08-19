<?php

declare(strict_types=1);

namespace Modules\Agronom\Enums;

enum RequestCalendarRunStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case EXCLUDED = 'excluded';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
}
