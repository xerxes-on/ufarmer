<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Modules\AgroCalendar\Models\CalendarRun;

class PopularCropsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Popular Crops (by Calendar Runs)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $cropCounts = CalendarRun::query()
            ->select('crop_id', DB::raw('COUNT(id) as count'))
            ->whereNotNull('crop_id')
            ->groupBy('crop_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $cropIds = $cropCounts->pluck('crop_id')->toArray();
        $crops = \Modules\Crops\Models\Crop::query()
            ->whereIn('id', $cropIds)
            ->get()
            ->keyBy('id');

        $labels = [];
        $data = [];

        foreach ($cropCounts as $item) {
            $crop = $crops->get($item->crop_id);
            $name = $crop ? ($crop->name ?? 'Unknown') : 'Unknown';
            $labels[] = $name;
            $data[] = $item->count;
        }

        $colors = [
            'rgba(34, 197, 94, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(251, 191, 36, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(20, 184, 166, 0.8)',
            'rgba(249, 115, 22, 0.8)',
            'rgba(107, 114, 128, 0.8)',
            'rgba(99, 102, 241, 0.8)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Calendar Runs',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
