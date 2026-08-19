<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Modules\AgroCalendar\Models\CalendarRun;
use Modules\Agronom\Models\AgronomDetail;
use Modules\Agronom\Models\ServiceRequest;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;
use Modules\Crops\Models\Crop;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $usersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $agronomCounts = AgronomDetail::selectRaw('COUNT(*) as total, COUNT(user_id) as registered')->first();
        $totalAgronoms = (int) $agronomCounts->total;
        $registeredAgronoms = (int) $agronomCounts->registered;
        $unregisteredAgronoms = $totalAgronoms - $registeredAgronoms;
        $agronomsThisMonth = AgronomDetail::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalAreas = Area::count();
        $areasThisMonth = Area::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalCalendarRuns = CalendarRun::count();
        $activeCalendarRuns = CalendarRun::whereNull('completed_on')->count();

        $pendingRequests = ServiceRequest::where('status', 'pending')->count();
        $completedRequests = ServiceRequest::where('status', 'completed')->count();

        $totalCrops = Crop::count();

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description($usersThisMonth.' this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getUsersChartData()),

            Stat::make('Agronomist Profiles', number_format($totalAgronoms))
                ->description("{$registeredAgronoms} registered · {$unregisteredAgronoms} directory-only")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->extraAttributes([
                    'title' => 'All agronomist profiles: registered app users plus admin-added directory listings with no account. Not a subset of Total Users.',
                ]),

            Stat::make('Farm Areas', number_format($totalAreas))
                ->description($areasThisMonth.' this month')
                ->descriptionIcon('heroicon-m-map')
                ->color('warning'),

            Stat::make('Calendar Runs', number_format($totalCalendarRuns))
                ->description($activeCalendarRuns.' active')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Pending Requests', number_format($pendingRequests))
                ->description($completedRequests.' completed')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingRequests > 10 ? 'danger' : 'success'),

            Stat::make('Total Crops', number_format($totalCrops))
                ->description('In database')
                ->descriptionIcon('heroicon-m-sun')
                ->color('success'),
        ];
    }

    private function getUsersChartData(): array
    {
        $data = User::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        return count($data) > 0 ? $data : [0, 0, 0, 0, 0, 0, 0];
    }
}
