<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

/**
 * Analysis kind — mirrors ufarm-ai-calendar's Modules\FieldData\Enums\AnalysisType.
 * Used on the MQ wire under agrocalendar.command.generate payload.analyses[].type.
 */
enum AnalysisType: string
{
    case Soil = 'soil';
    case Water = 'water';
    case Leaf = 'leaf';
}
