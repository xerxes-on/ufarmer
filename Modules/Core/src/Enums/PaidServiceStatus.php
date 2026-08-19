<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum PaidServiceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Deprecated = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Deprecated => 'Deprecated',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Inactive->value => self::Inactive->label(),
            self::Deprecated->value => self::Deprecated->label(),
        ];
    }
}
