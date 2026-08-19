<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeCreateOrSelectTest extends TestCase
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

        config([
            'services.authbridge.jsonrpc.endpoint' => 'https://ufarm-auth.test/api/v1/jsonrpc',
            'services.authbridge.jsonrpc.username' => 'jsonrpc-user',
            'services.authbridge.jsonrpc.password' => 'jsonrpc-secret',
            'services.authbridge.employee_application_alias' => 'admin_panel',
        ]);
    }

    public function test_creating_a_new_user_resolves_auth_id_via_sso(): void
    {
        Http::fake([
            '*' => Http::response(['result' => ['success' => true, 'user_id' => 778899, 'created' => true]]),
        ]);

        $userId = $this->createUserWithAuthId('998900011122');
        $user = User::find($userId);

        $this->assertNotNull($user);
        $this->assertSame(778899, $user->auth_id);

        Http::assertSent(fn ($request) => $request->data()['params']['phone'] === '998900011122'
            && $request->data()['params']['application_alias'] === 'admin_panel'
            && $request->data()['params']['entity_id'] === $user->id);
    }

    public function test_promotes_an_existing_local_user_by_selecting_them_and_assigning_a_role(): void
    {
        $existing = User::create(['phone' => '998900099887']);
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'guard_name' => 'web']);

        $record = $this->handleRecordCreation([
            'user_id' => $existing->id,
            'roles' => ['admin'],
        ]);

        $this->assertSame($existing->id, $record->id);
        $this->assertTrue(
            DB::table('model_has_roles')
                ->where(['role_id' => $roleId, 'model_id' => $existing->id, 'model_type' => User::class])
                ->exists()
        );
    }

    private function createUserWithAuthId(string $phone, ?string $password = null): int
    {
        $method = new ReflectionMethod(CreateEmployee::class, 'createUserWithAuthId');
        $method->setAccessible(true);

        return $method->invoke(null, $phone, $password);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleRecordCreation(array $data): User
    {
        $page = new CreateEmployee;
        $method = new ReflectionMethod(CreateEmployee::class, 'handleRecordCreation');
        $method->setAccessible(true);

        /** @var User $record */
        $record = $method->invoke($page, $data);

        return $record;
    }
}
