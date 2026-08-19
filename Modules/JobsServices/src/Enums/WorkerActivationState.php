<?php

declare(strict_types=1);

namespace Modules\JobsServices\Enums;

enum WorkerActivationState: string
{
    case Activated = 'activated';
    case AuthMissing = 'auth_missing';
    case NotActivated = 'not_activated';
    case NotTracked = 'not_tracked';
}
