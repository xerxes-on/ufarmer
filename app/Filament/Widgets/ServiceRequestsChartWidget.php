<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Modules\Agronom\Models\ServiceRequest;

class ServiceRequestsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Service Requests by Status';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $statuses = ServiceRequest::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusLabels = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
        ];

        $statusColors = [
            'pending' => 'rgb(251, 191, 36)',
            'confirmed' => 'rgb(59, 130, 246)',
            'in_progress' => 'rgb(139, 92, 246)',
            'completed' => 'rgb(34, 197, 94)',
            'cancelled' => 'rgb(107, 114, 128)',
            'rejected' => 'rgb(239, 68, 68)',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($statuses as $status => $count) {
            $labels[] = $statusLabels[$status] ?? ucfirst($status);
            $data[] = $count;
            $colors[] = $statusColors[$status] ?? 'rgb(107, 114, 128)';
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
