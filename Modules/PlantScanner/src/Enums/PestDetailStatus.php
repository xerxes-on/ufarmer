<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Enums;

enum PestDetailStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
        };
    }
}
