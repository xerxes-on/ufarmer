<?php

declare(strict_types=1);

namespace Ufarm\Premium\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';

    public function isTerminal(): bool
    {
        return $this === self::INACTIVE || $this === self::EXPIRED;
    }
}
