<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

enum AccuracyFactorAction: string
{
    case Analysis = 'analysis';
    case Scanner = 'scanner';
    case Marketplace = 'marketplace';
    case FieldHistory = 'field_history';

    public function label(): string
    {
        return match ($this) {
            self::Analysis => 'Analysis',
            self::Scanner => 'Scanner',
            self::Marketplace => 'Marketplace',
            self::FieldHistory => 'Field History',
        };
    }
}
