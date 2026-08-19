<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource;
use Tests\TestCase;

class UzgidrometAuthorizationTest extends TestCase
{
    public function test_user_panel_access_does_not_require_removed_role_tables(): void
    {
        $user = new User;
        $user->email = 'allowed@example.test';
        config()->set('admin.panel_emails', [$user->email]);

        $this->assertTrue($user->canAccessPanel(Panel::make()->id('uzgidromet')));
    }

    public function test_uzgidromet_resource_access_uses_authenticated_session(): void
    {
        $user = new User;
        $user->id = 586;

        $this->actingAs($user);

        $this->assertTrue(UzgidrometFileResource::canViewAny());
        $this->assertTrue(UzgidrometFileResource::canCreate());
    }
}
