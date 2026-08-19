<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum AppSettingType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Float = 'float';
    case Boolean = 'boolean';
    case Json = 'json';
    case Enum = 'enum';

    public function label(): string
    {
        return match ($this) {
            self::String => 'String',
            self::Integer => 'Integer',
            self::Float => 'Float',
            self::Boolean => 'Boolean',
            self::Json => 'JSON',
            self::Enum => 'Enum',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::String->value => self::String->label(),
            self::Integer->value => self::Integer->label(),
            self::Float->value => self::Float->label(),
            self::Boolean->value => self::Boolean->label(),
            self::Json->value => self::Json->label(),
            self::Enum->value => self::Enum->label(),
        ];
    }
}
