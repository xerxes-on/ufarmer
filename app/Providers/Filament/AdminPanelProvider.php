<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\NavigationGroup;
use App\Filament\Pages\Auth\SsoLogin;
use App\Http\Middleware\AuditAdminPanelActivity;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\AgroCalendar\Filament\AgroCalendarPlugin;
use Modules\Agronom\Filament\AgronomPlugin;
use Modules\AgroPrices\Filament\AgroPricesPlugin;
use Modules\AICalculation\Filament\AICalculationPlugin;
use Modules\Analysis\Filament\AnalysisPlugin;
use Modules\Billing\Filament\BillingPlugin;
use Modules\Core\Filament\CorePlugin;
use Modules\Crops\Filament\CropsPlugin;
use Modules\Exporter\Filament\ExporterPlugin;
use Modules\General\Filament\GeneralPlugin;
use Modules\Harvest\Filament\HarvestPlugin;
use Modules\JobsServices\Filament\JobsServicesPlugin;
use Modules\PlantScanner\Filament\PlantScannerPlugin;
use Modules\Predictions\Filament\PredictionsPlugin;

// use Ufarm\Premium\Support\PremiumPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(SsoLogin::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->plugins([
                new AICalculationPlugin,
                new AnalysisPlugin,
                new AgroCalendarPlugin,
                new AgronomPlugin,
                new AgroPricesPlugin,
                new BillingPlugin,
                //                new PremiumPlugin,
                new CorePlugin,
                new CropsPlugin,
                new GeneralPlugin,
                new HarvestPlugin,
                new JobsServicesPlugin,
                new PlantScannerPlugin,
                new PredictionsPlugin,
                new ExporterPlugin,
                FilamentShieldPlugin::make(),
            ])
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->navigationGroups(NavigationGroup::ordered())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                AuditAdminPanelActivity::class,
            ])
            ->persistentMiddleware([
                AuditAdminPanelActivity::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
