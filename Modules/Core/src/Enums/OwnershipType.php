<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum OwnershipType: string
{
    case Owned = 'owned';
    case Rented = 'rented';
    case Leased = 'leased';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'Owned',
            self::Rented => 'Rented',
            self::Leased => 'Leased',
            self::Shared => 'Shared',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Owned->value => self::Owned->label(),
            self::Rented->value => self::Rented->label(),
            self::Leased->value => self::Leased->label(),
            self::Shared->value => self::Shared->label(),
        ];
    }
}
