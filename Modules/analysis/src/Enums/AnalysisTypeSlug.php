<?php

declare(strict_types=1);

namespace Modules\Analysis\Enums;

enum AnalysisTypeSlug: string
{
    case WithoutCrops = 'without_crops';
    case WithCrops = 'with_crops';
    case Calendar = 'calendar';

    public function label(): string
    {
        return match ($this) {
            self::WithoutCrops => 'Without Crops',
            self::WithCrops => 'With Crops',
            self::Calendar => 'Calendar',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
