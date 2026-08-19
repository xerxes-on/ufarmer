<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeResourceScopingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // See tests/Unit/UserEnsurePanelRoleTest.php for why an isolated
        // in-memory sqlite connection (rather than RefreshDatabase) is used
        // here — the real `users` table's migrations live outside this repo
        // (shared-DB convention, see ufarm-core/CLAUDE.md).
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->softDeletes();
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
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_primary');
        });
    }

    public function test_users_without_a_role_are_excluded(): void
    {
        User::create(['name' => 'Farmer', 'phone' => '998900000001']);

        $this->assertSame(0, EmployeeResource::getEloquentQuery()->count());
    }

    public function test_users_with_any_role_are_included(): void
    {
        $employee = User::create(['name' => 'Content Manager', 'phone' => '998900000002']);
        $roleId = DB::table('roles')->insertGetId(['name' => 'content_manager', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $employee->id,
        ]);

        $ids = EmployeeResource::getEloquentQuery()->pluck('id');

        $this->assertSame([$employee->id], $ids->all());
    }
}
