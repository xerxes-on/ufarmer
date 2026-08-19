<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum Currency: string
{
    case UZS = 'UZS';
    case USD = 'USD';

    public function label(): string
    {
        return match ($this) {
            self::UZS => 'UZS',
            self::USD => 'USD',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::UZS => "so'm",
            self::USD => '$',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::UZS->value => self::UZS->label(),
            self::USD->value => self::USD->label(),
        ];
    }
}
