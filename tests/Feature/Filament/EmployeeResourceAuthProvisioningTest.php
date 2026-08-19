<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeResourceAuthProvisioningTest extends TestCase
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

        config([
            'services.authbridge.jsonrpc.endpoint' => 'https://ufarm-auth.test/api/v1/jsonrpc',
            'services.authbridge.jsonrpc.username' => 'jsonrpc-user',
            'services.authbridge.jsonrpc.password' => 'jsonrpc-secret',
            'services.authbridge.employee_application_alias' => 'admin_panel',
        ]);
    }

    public function test_create_with_valid_phone_and_no_password_resolves_auth_id(): void
    {
        Http::fake([
            '*' => Http::response(['result' => ['success' => true, 'user_id' => 111, 'created' => true]]),
        ]);

        $user = $this->createUserWithAuthId('998901112233');

        $this->assertSame(111, $user->auth_id);

        Http::assertSent(fn ($request) => ! array_key_exists('password', $request->data()['params'])
            && $request->data()['params']['entity_id'] === $user->id);
    }

    public function test_create_with_password_forwards_it_to_rpc_but_never_persists_it_locally(): void
    {
        Http::fake([
            '*' => Http::response(['result' => ['success' => true, 'user_id' => 222, 'created' => true]]),
        ]);

        $user = $this->createUserWithAuthId('998901112244', 'a-strong-password');

        $this->assertSame(222, $user->auth_id);
        $this->assertNull($user->password);

        Http::assertSent(fn ($request) => $request->data()['params']['password'] === 'a-strong-password');
    }

    public function test_create_with_existing_user_scenario_resolves_without_forwarding_password(): void
    {
        Http::fake([
            '*' => Http::response(['result' => ['success' => true, 'user_id' => 333, 'created' => false]]),
        ]);

        $user = $this->createUserWithAuthId('998901112255');

        $this->assertSame(333, $user->auth_id);

        Http::assertSent(fn ($request) => ! array_key_exists('password', $request->data()['params']));
    }

    public function test_edit_with_already_set_auth_id_still_calls_rpc_with_correct_entity_id(): void
    {
        $record = User::create([
            'name' => 'Existing Employee',
            'phone' => '998901112266',
            'auth_id' => 999,
        ]);

        Http::fake([
            '*' => Http::response(['result' => ['success' => true, 'user_id' => 999, 'created' => false]]),
        ]);

        $updated = $this->handleEdit($record, [
            'name' => 'Existing Employee (renamed)',
            'phone' => '998901112266',
        ]);

        Http::assertSent(fn ($request) => $request->data()['method'] === 'findOrCreateUser'
            && $request->data()['params']['entity_id'] === $record->id);

        $this->assertSame(999, $updated->auth_id);
    }

    /**
     * The "create a brand-new user" flow now lives behind the Employees
     * create page's user-picker Select (`createOptionUsing`), extracted into
     * `CreateEmployee::createUserWithAuthId()` so it stays reflectable/
     * testable the same way as `EditEmployee::handleRecordUpdate()`, without
     * needing a full Livewire form interaction.
     */
    private function createUserWithAuthId(string $phone, ?string $password = null): User
    {
        $method = new ReflectionMethod(CreateEmployee::class, 'createUserWithAuthId');
        $method->setAccessible(true);

        $userId = $method->invoke(null, $phone, $password);

        return User::findOrFail($userId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleEdit(User $record, array $data): User
    {
        $page = new EditEmployee;
        $method = new ReflectionMethod(EditEmployee::class, 'handleRecordUpdate');
        $method->setAccessible(true);

        /** @var User $updated */
        $updated = $method->invoke($page, $record, $data);

        return $updated;
    }
}
