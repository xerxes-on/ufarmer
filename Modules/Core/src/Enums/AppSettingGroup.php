<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum AppSettingGroup: string
{
    case General = 'general';
    case Area = 'area';
    case Calendar = 'calendar';
    case User = 'user';
    case App = 'app';
    case Referral = 'referral';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Area => 'Area',
            self::Calendar => 'Calendar',
            self::User => 'User',
            self::App => 'App',
            self::Referral => 'Referral',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::General->value => self::General->label(),
            self::Area->value => self::Area->label(),
            self::Calendar->value => self::Calendar->label(),
            self::User->value => self::User->label(),
            self::App->value => self::App->label(),
            self::Referral->value => self::Referral->label(),
        ];
    }
}
