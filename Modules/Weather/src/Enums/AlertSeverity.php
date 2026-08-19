<?php

declare(strict_types=1);

namespace Modules\Weather\Enums;

enum AlertSeverity: string
{
    case CRITICAL = 'critical';
    case WARNING = 'warning';
    case INFO = 'info';
    case OK = 'ok';

    public function color(): string
    {
        return match ($this) {
            self::CRITICAL => 'danger',
            self::WARNING => 'warning',
            self::INFO => 'info',
            self::OK => 'success',
        };
    }

    public function getColorName(): string
    {
        return match ($this) {
            self::CRITICAL => 'red',
            self::WARNING => 'amber',
            self::INFO => 'blue',
            self::OK => 'green',
        };
    }
}
