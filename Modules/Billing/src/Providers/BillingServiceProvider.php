<?php

declare(strict_types=1);

namespace Modules\Billing\Providers;

use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    protected string $name = 'Billing';

    protected string $nameLower = 'billing';

    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }

    public function provides(): array
    {
        return [];
    }
}
