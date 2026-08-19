<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Uzgidromet\Models\UzgidrometFile;
use Modules\Uzgidromet\Observers\UzgidrometFileObserver;

/**
 * Registers `uzgidromet::*` translation namespace so resource labels in
 * the Uzgidromet panel resolve via `__('uzgidromet::filament.xxx')` for
 * uz / ru / en. Loaded by bootstrap/providers.php — admin-api doesn't
 * auto-discover module providers from per-module composer.json.
 *
 * Also binds the UzgidrometFile observer so every new upload posts an
 * HTML message to the operations Telegram channel.
 */
class UzgidrometServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'uzgidromet');

        UzgidrometFile::observe(UzgidrometFileObserver::class);
    }
}
