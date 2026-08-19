<?php

declare(strict_types=1);

namespace Modules\Predictions\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Modules\AgroCalendar\Models\CalendarRun;

class CalendarRunPredictionWidget extends Widget
{
    protected static string $view = 'predictions::filament.widgets.calendar-run-prediction';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    public function mount(?Model $record = null): void
    {
        $this->record = $record;
    }

    protected function getViewData(): array
    {
        if (! $this->record instanceof CalendarRun) {
            return [
                'hasPrediction' => false,
                'run' => null,
                'factors' => [],
            ];
        }

        $run = $this->record->load(['accuracyFactors.accuracyFactor', 'crop']);

        $factors = $run->accuracyFactors->map(function ($calendarFactor) {
            $factor = $calendarFactor->accuracyFactor;

            return [
                'name' => $factor?->getTranslation('name', 'uz') ?? 'Unknown',
                'boost_pct' => $calendarFactor->boost_pct ?? $factor?->boost_pct ?? 0,
                'description' => $factor?->getTranslation('description', 'uz'),
                'image_url' => $factor?->image_url,
            ];
        });

        $totalBoost = $factors->sum('boost_pct');
        $maxBoost = max($factors->max('boost_pct') ?? 1, 1);

        return [
            'hasPrediction' => $run->hasPrediction(),
            'run' => $run,
            'factors' => $factors->toArray(),
            'totalBoost' => $totalBoost,
            'maxBoost' => $maxBoost,
            'predictedYield' => $run->predicted_yield,
            'actualYield' => $run->actual_yield,
            'yieldConfidence' => $run->yield_confidence,
            'predictedHarvestDate' => $run->predicted_harvest_date,
            'actualHarvestDate' => $run->actual_harvest_date,
            'accuracyPct' => $run->accuracy_pct,
            'daysUntilHarvest' => $run->getDaysUntilHarvest(),
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
