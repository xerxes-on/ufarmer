<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;

final class SsoLogin extends Login
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        if (config('authbridge.sso.enabled')
            && filled(config('authbridge.sso.base_url'))
            && filled(config('authbridge.sso.client_id'))
            && filled(config('authbridge.sso.redirect_uri'))) {
            redirect()->to(route('sso.redirect'));

            return;
        }

        parent::mount();
    }
}
