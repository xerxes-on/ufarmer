<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Widgets\ServiceRequestsChartWidget;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Modules\AgroCalendar\Filament\Resources\CalendarRunResource;
use Modules\Agronom\Filament\Pages\AgronomSettings;
use Modules\Agronom\Filament\Resources\ServiceTypeResource;
use Modules\Core\Filament\Resources\UserResource;
use Modules\Crops\Filament\Resources\CropResource;
use Tests\TestCase;

/**
 * Guards the agronom-admin -> admin panel merge: the four plugins that used
 * to live on their own panel must be reachable on `admin`, and the old panel
 * must be gone.
 *
 * These assertions are all registration/routing level, so they need no
 * database — unlike the sibling Employee tests, which stub `users`.
 */
class AdminPanelMergeTest extends TestCase
{
    public function test_merged_plugins_are_served_from_the_admin_panel(): void
    {
        $resources = Filament::getPanel('admin')->getResources();

        // One representative resource per previously-separate plugin.
        $this->assertContains(CropResource::class, $resources, 'CropsPlugin not merged');
        $this->assertContains(UserResource::class, $resources, 'CorePlugin not merged');
        $this->assertContains(ServiceTypeResource::class, $resources, 'AgronomPlugin not merged');
        $this->assertContains(CalendarRunResource::class, $resources, 'AgroCalendarPlugin not merged');
    }

    public function test_agronom_settings_page_is_registered_with_its_git_managed_default_image(): void
    {
        $this->assertContains(AgronomSettings::class, Filament::getPanel('admin')->getPages());
        $this->assertStringContainsString('/admin/agronom/settings', AgronomSettings::getUrl());
        $this->assertFileExists(public_path('images/agronom/default-profile.png'));
    }

    /**
     * Filament::getPanel() silently falls back to the default panel for an
     * unknown id, so assert against the registered set instead.
     */
    public function test_agronom_admin_panel_is_no_longer_registered(): void
    {
        $panelIds = array_keys(Filament::getPanels());

        $this->assertNotContains('agronom-admin', $panelIds);
        $this->assertContains('admin', $panelIds);
    }

    public function test_merged_resources_route_under_admin(): void
    {
        $this->assertStringContainsString('/admin/crops', CropResource::getUrl('index'));
        $this->assertStringContainsString('/admin/calendar-runs', CalendarRunResource::getUrl('index'));
    }

    /**
     * The relation manager used to hardcode "/agronom-admin/calendar-runs/{id}",
     * which would 404 after the merge. It now derives the URL from the resource.
     */
    public function test_calendar_run_view_url_is_not_hardcoded_to_the_old_panel(): void
    {
        $url = CalendarRunResource::getUrl('view', ['record' => 1]);

        $this->assertStringNotContainsString('agronom-admin', $url);
        $this->assertStringContainsString('/admin/calendar-runs/1', $url);
    }

    public function test_retired_agrocalendar_resources_are_not_registered(): void
    {
        foreach ([
            'spray-records',
            'task-evidences',
            'template-task-statuses',
            'weather-thresholds',
            'params',
            'param-options',
            'app-settings',
            'irrigation-source-types',
            'harvest-growth-types',
            'harvest-quality-grades',
            'area-soil-profiles',
        ] as $resource) {
            $this->assertFalse(Route::has("filament.admin.resources.{$resource}.index"));
        }
    }

    public function test_shield_generation_keeps_git_managed_policies_unchanged(): void
    {
        $this->assertSame('permissions', config('filament-shield.generator.option'));
    }

    public function test_admin_dashboard_has_no_widgets(): void
    {
        $this->assertSame([], Filament::getPanel('admin')->getWidgets());
    }

    public function test_core_infolist_views_are_registered(): void
    {
        $this->assertTrue(view()->exists('core::filament.infolists.components.yandex-map-entry'));
    }

    public function test_removed_widget_aliases_remain_loadable_for_stale_tabs(): void
    {
        $this->assertInstanceOf(
            ServiceRequestsChartWidget::class,
            Livewire::new('app.filament.widgets.service-requests-chart-widget'),
        );
    }
}
