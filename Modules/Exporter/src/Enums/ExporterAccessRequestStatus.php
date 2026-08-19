<?php

declare(strict_types=1);

namespace Modules\Exporter\Enums;

enum ExporterAccessRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
