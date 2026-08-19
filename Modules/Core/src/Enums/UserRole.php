<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum UserRole: string
{
    case Farmer = 'farmer';
    case Agronom = 'agronom';

    public function label(): string
    {
        return match ($this) {
            self::Farmer => 'Farmer',
            self::Agronom => 'Agronom',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Farmer->value => self::Farmer->label(),
            self::Agronom->value => self::Agronom->label(),
        ];
    }
}
