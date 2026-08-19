<?php

namespace App\Providers;

use App\Services\AdminActivityLogger;
use App\Services\AuthBridge\DatabaseFallbackApplicationRepository;
use App\Support\AdminActivityContext;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\ParentCrop;
use Modules\Crops\Observers\CropChangePublisher;
use Modules\General\Models\Article;
use Modules\JobsServices\Console\BackfillImportedOfferLocales;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Xerxes\AuthBridge\Contracts\ApplicationRepositoryContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApplicationRepositoryContract::class, DatabaseFallbackApplicationRepository::class);
        $this->app->scoped(AdminActivityContext::class);
        $this->commands([BackfillImportedOfferLocales::class]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        Gate::define('viewPulse', fn ($user) => method_exists($user, 'hasRole') && $user->hasRole('super_admin'));

        // Shield generates every policy under App\Policies regardless of the
        // model's own namespace. Laravel's default guesser only matches
        // App\Models\X <-> App\Policies\XPolicy, so Modules\*\Models\* models
        // never resolved a policy and Filament defaulted them to open access.
        Gate::guessPolicyNamesUsing(function (string $modelClass): ?string {
            $policy = 'App\\Policies\\'.class_basename($modelClass).'Policy';

            return class_exists($policy) ? $policy : null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(config('app.supported_locales'))
                ->labels([
                    'en' => 'English',
                    'ru' => 'Русский',
                    'uz' => 'Oʻzbekcha',
                ])
                ->displayLocale(app()->getLocale())
                ->visible(true, true);
        });

        // Policy checks alone don't stop super_admin (Shield's Gate::before
        // bypasses them), so renaming/deleting the super_admin role itself
        // — which would break or hijack that bypass — is blocked here at
        // the Eloquent layer, which Gate::before has no effect on.
        Role::updating(fn (Role $role): bool => ! ($role->getOriginal('name') === 'super_admin' && $role->isDirty('name')));
        Role::deleting(fn (Role $role): bool => $role->name !== 'super_admin');

        // Keep stale dashboard tabs hydratable without registering widgets on new dashboards.
        foreach ([
            'app.filament.widgets.calendar-runs-chart-widget' => \App\Filament\Widgets\CalendarRunsChartWidget::class,
            'app.filament.widgets.latest-service-requests-widget' => \App\Filament\Widgets\LatestServiceRequestsWidget::class,
            'app.filament.widgets.latest-users-widget' => \App\Filament\Widgets\LatestUsersWidget::class,
            'app.filament.widgets.popular-crops-chart-widget' => \App\Filament\Widgets\PopularCropsChartWidget::class,
            'app.filament.widgets.service-requests-chart-widget' => \App\Filament\Widgets\ServiceRequestsChartWidget::class,
            'app.filament.widgets.stats-overview-widget' => \App\Filament\Widgets\StatsOverviewWidget::class,
            'app.filament.widgets.users-chart-widget' => \App\Filament\Widgets\UsersChartWidget::class,
        ] as $name => $class) {
            Livewire::component($name, $class);
        }

        $this->registerAdminActivityLogging();
        $this->registerAdminCropWriteProtection();
        $this->registerCropChangePublishing();

        // ufarm-api owns the `articles` table's media morph type as
        // 'Modules\Core\Models\Article'. Without this map, Spatie stores
        // this app's own FQCN instead, and images uploaded here become
        // invisible to the public API reading the same media table.
        Relation::morphMap([
            \Modules\Core\Models\Article::class => Article::class,
        ]);
    }

    private function registerAdminActivityLogging(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored', 'forceDeleted'] as $event) {
            Event::listen("eloquent.{$event}: *", static function (string $eventName, array $payload) use ($event): void {
                $model = $payload[0] ?? null;

                if ($model instanceof Model) {
                    app(AdminActivityLogger::class)->log($model, $event);
                }
            });
        }
    }

    private function registerAdminCropWriteProtection(): void
    {
        $blockCreate = static function (Model $model): void {
            if (app(AdminActivityContext::class)->isActive()) {
                throw ValidationException::withMessages([
                    'record' => class_basename($model).' creation is disabled in the admin panel.',
                ]);
            }
        };

        $blockReactivation = static function (Model $model): void {
            if (! app(AdminActivityContext::class)->isActive()) {
                return;
            }

            if ($model->isDirty('is_active')
                && ! (bool) $model->getRawOriginal('is_active')
                && (bool) $model->getAttribute('is_active')) {
                throw ValidationException::withMessages([
                    'is_active' => class_basename($model).' reactivation is disabled in the admin panel.',
                ]);
            }
        };

        Crop::creating($blockCreate);
        Crop::updating($blockReactivation);
        ParentCrop::creating($blockCreate);
        ParentCrop::updating($blockReactivation);
    }

    /**
     * Publish crop changes made in this panel to RabbitMQ consumers.
     *
     * Lives here rather than in CropsServiceProvider because the per-module
     * providers under Modules/*\/src/Providers are not registered in
     * bootstrap/providers.php — only Uzgidromet's is — so anything wired
     * there never boots.
     *
     * Registered through closures rather than Model::observe(): Laravel
     * stores observers by class name and re-resolves them from the container
     * when an event fires, which has previously broken publishing in this
     * codebase (UFARM-713). A closure captures the instance verbatim.
     */
    private function registerCropChangePublishing(): void
    {
        $publisher = new CropChangePublisher;

        Crop::saved(static fn (Crop $crop) => $publisher->cropSaved($crop));
        Crop::deleted(static fn (Crop $crop) => $publisher->cropDeleted($crop));

        ParentCrop::saved(static fn (ParentCrop $parentCrop) => $publisher->parentCropSaved($parentCrop));
        ParentCrop::deleted(static fn (ParentCrop $parentCrop) => $publisher->parentCropDeleted($parentCrop));

        // Image-only edits never touch the owning model — spatie/media-library
        // writes straight to the `media` table — so they are caught there.
        Media::created(static fn (Media $media) => $publisher->mediaChanged($media));
        Media::updated(static fn (Media $media) => $publisher->mediaChanged($media));
        Media::deleted(static fn (Media $media) => $publisher->mediaChanged($media));
    }
}
