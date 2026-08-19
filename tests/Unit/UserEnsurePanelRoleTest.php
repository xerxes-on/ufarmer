<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserEnsurePanelRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase would migrate this app's *entire* real migration
        // set (Pulse, outbox, ...) against whatever DB_CONNECTION resolves
        // to — far more than this test needs, and the real `users` /
        // permission-tables migrations live outside this repo (shared-DB
        // convention — see ufarm-core/CLAUDE.md), so they aren't available
        // to depend on from here either. Use an isolated in-memory sqlite
        // connection with only the schema `ensurePanelRole()` touches.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
    }

    public function test_bootstrap_auth_id_gets_super_admin(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        config(['filament-shield.bootstrap_super_admin_auth_ids' => ['42']]);

        $user = new User;
        $user->auth_id = 42;
        $user->save();

        $user->ensurePanelRole();

        $this->assertTrue($user->fresh()->hasRole('super_admin'));
    }

    public function test_non_bootstrap_auth_id_gets_no_role(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        config(['filament-shield.bootstrap_super_admin_auth_ids' => ['42']]);

        $user = new User;
        $user->auth_id = 999;
        $user->save();

        $user->ensurePanelRole();

        $this->assertCount(0, $user->fresh()->roles);
    }
}
