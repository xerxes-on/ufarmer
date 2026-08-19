<?php

declare(strict_types=1);

namespace Ufarm\Premium\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\User;
use Ufarm\Premium\Console\Commands\SeedPremiumPlansCommand;
use Ufarm\Premium\Jobs\DeactivateExpiredSubscriptionsJob;
use Ufarm\Premium\Middleware\EnsurePremiumSubscription;
use Ufarm\Premium\Models\PremiumPlan;
use Ufarm\Premium\Models\Subscription;
use Ufarm\Premium\Observers\SubscriptionObserver;
use Ufarm\Premium\Services\SubscriptionNotifier;
use Ufarm\Premium\Support\PremiumAccess;

class PremiumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/premium.php', 'premium');

        $this->app->singleton(SubscriptionNotifier::class);
        $this->app->singleton(PremiumAccess::class, fn () => new PremiumAccess);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'premium');
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerUserRelations();
        $this->registerObservers();
        $this->registerMiddlewareAlias();
        $this->registerSchedule();
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../config/premium.php' => config_path('premium.php'),
        ], 'premium-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'premium-migrations');

        $this->publishes([
            __DIR__.'/../../lang' => $this->app->langPath('vendor/premium'),
        ], 'premium-translations');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SeedPremiumPlansCommand::class,
        ]);
    }

    protected function registerUserRelations(): void
    {
        User::resolveRelationUsing('premiumSubscriptions', function (User $user) {
            return $user->hasMany(Subscription::class);
        });

        User::resolveRelationUsing('premiumPlans', function (User $user) {
            return $user->belongsToMany(PremiumPlan::class, Subscription::TABLE, 'user_id', 'premium_plan_id')
                ->using(Subscription::class)
                ->withPivot([
                    'id',
                    'status',
                    'starts_at',
                    'ends_at',
                    'canceled_at',
                    'renewed_at',
                    'application_id',
                ])
                ->withTimestamps();
        });
    }

    protected function registerObservers(): void
    {
        Subscription::observe(SubscriptionObserver::class);
    }

    protected function registerMiddlewareAlias(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('premium', EnsurePremiumSubscription::class);
    }

    protected function registerSchedule(): void
    {
        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(new DeactivateExpiredSubscriptionsJob)
                ->dailyAt(config('premium.jobs.expiry_check_at', '01:00'));
        });
    }
}
