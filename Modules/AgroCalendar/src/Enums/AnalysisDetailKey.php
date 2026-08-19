<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

/**
 * Top-level detail group keys used when shipping analyses on the MQ wire.
 * Mirrors ufarm-ai-calendar's Modules\FieldData\Enums\AnalysisDetailKey exactly —
 * keep the values in sync. Eldor's SeasonIntakeService stores one AnalysisDetail
 * row per entry.
 */
enum AnalysisDetailKey: string
{
    case SoilProperties = 'soil_properties';
    case Macronutrients = 'macronutrients';
    case Micronutrients = 'micronutrients';
    case Carbonates = 'carbonates';
    case HydraulicProperties = 'hydraulic_properties';
    case WaterQuality = 'water_quality';
}
