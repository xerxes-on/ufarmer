<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SsoLoginRedirectTest extends TestCase
{
    public function test_login_page_redirects_to_sso_when_configured(): void
    {
        config([
            'authbridge.sso.enabled' => true,
            'authbridge.sso.base_url' => 'https://ufarm-auth.test',
            'authbridge.sso.client_id' => 'test-client-id',
            'authbridge.sso.redirect_uri' => 'https://ufarm-admin-api.test/auth/callback',
        ]);

        $this->get('/admin/login')
            ->assertRedirect(route('sso.redirect'));
    }

    public function test_login_page_renders_normally_when_sso_not_configured(): void
    {
        config(['authbridge.sso.enabled' => false]);

        $this->get('/admin/login')
            ->assertOk();
    }
}
