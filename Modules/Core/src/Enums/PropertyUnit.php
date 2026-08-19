<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum PropertyUnit: string
{
    case Hectare = 'ga';
    case Sotka = 'sotka';
    case SquareMeter = 'm2';

    public function label(): string
    {
        return match ($this) {
            self::Hectare => 'Hectares (ha)',
            self::Sotka => 'Sotka',
            self::SquareMeter => 'Square meters (m²)',
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::Hectare => 'ha',
            self::Sotka => 'sotka',
            self::SquareMeter => 'm²',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Hectare->value => self::Hectare->label(),
            self::Sotka->value => self::Sotka->label(),
            self::SquareMeter->value => self::SquareMeter->label(),
        ];
    }
}
