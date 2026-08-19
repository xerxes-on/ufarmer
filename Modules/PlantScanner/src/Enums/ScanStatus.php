<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Enums;

enum ScanStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getProgressPercentage(): int
    {
        return match ($this) {
            self::Pending => 10,
            self::Processing => 50,
            self::Completed => 100,
            self::Failed => 0,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
