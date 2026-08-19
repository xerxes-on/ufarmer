<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Enums;

enum PriceStatus: string
{
    case Up = 'up';
    case Down = 'down';
    case Stable = 'stable';

    public function color(): string
    {
        return match ($this) {
            self::Up => 'success',
            self::Down => 'danger',
            self::Stable => 'gray',
        };
    }
}
