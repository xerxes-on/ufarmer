<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Modules\Core\Models\User as BaseUser;
use Spatie\Permission\Traits\HasRoles;
use Xerxes\AuthBridge\Contracts\AuthBridgeUserContract;
use Xerxes\AuthBridge\Traits\HasAuthBridgeColumns;

class User extends BaseUser implements AuthBridgeUserContract, FilamentUser
{
    use HasAuthBridgeColumns;
    use HasRoles;

    /**
     * The `admin` panel's entry is gated upstream: ufarm-auth only issues an
     * OAuth code to users attached to the `admin_panel` application, and
     * SsoController re-verifies that via AuthBridge on callback. By the time
     * canAccessPanel() runs for `admin`, the user is already a member.
     *
     * Per-resource access within the panel is enforced by Shield policies.
     *
     * The `uzgidromet` panel keeps its own email allowlist (config/admin.php).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return true;
        }

        $allowed = config('admin.panel_emails', []);

        return $this->email !== null && in_array($this->email, $allowed, true);
    }

    /**
     * Bootstrap auth_ids (FILAMENT_SUPER_ADMIN_AUTH_IDS) get super_admin on
     * first SSO login. Everyone else starts role-less — a super_admin grants
     * roles/permissions manually via the Shield Role resource.
     */
    public function ensurePanelRole(): void
    {
        $bootstrapIds = array_map('strval', config('filament-shield.bootstrap_super_admin_auth_ids', []));

        if (in_array((string) $this->auth_id, $bootstrapIds, true)) {
            $this->assignRole('super_admin');
        }
    }
}
