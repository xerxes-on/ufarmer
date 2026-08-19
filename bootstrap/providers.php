<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\ModuleResourceServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\UzgidrometPanelProvider::class,
    Modules\Uzgidromet\Providers\UzgidrometServiceProvider::class,
];
