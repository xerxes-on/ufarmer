<?php

declare(strict_types=1);

namespace Modules\Agronom\Providers;

use Illuminate\Support\ServiceProvider;

class AgronomServiceProvider extends ServiceProvider
{
    protected string $name = 'Agronom';

    protected string $nameLower = 'agronom';

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
