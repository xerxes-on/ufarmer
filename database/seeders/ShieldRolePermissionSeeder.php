<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Grants the full-access panel roles every generated Shield permission.
 *
 * `shield:generate` creates permission rows but never attaches them to a
 * role, so any resource added to the panel is invisible to `admin` until
 * someone re-syncs. Both `admin` and `super_admin` have historically held
 * 100% of permissions, so re-syncing them to "all" is a no-op for existing
 * permissions and picks up newly generated ones automatically.
 *
 * Idempotent — safe to re-run on every deploy.
 */
final class ShieldRolePermissionSeeder extends Seeder
{
    /**
     * Roles that are intended to hold every permission. Narrower roles (if
     * any are added later) are managed through the Shield UI and are
     * deliberately not touched here.
     *
     * @var list<string>
     */
    private const FULL_ACCESS_ROLES = ['super_admin', 'admin'];

    public function run(): void
    {
        $permissions = Permission::pluck('id');

        if ($permissions->isEmpty()) {
            $this->command?->warn('No permissions found — run `shield:generate --all --panel=admin` first.');

            return;
        }

        foreach (self::FULL_ACCESS_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role === null) {
                $this->command?->warn("Role [{$roleName}] not found — skipped.");

                continue;
            }

            $role->permissions()->sync($permissions);

            $this->command?->info("Synced {$permissions->count()} permissions to [{$roleName}].");
        }

        Artisan::call('permission:cache-reset');
    }
}
