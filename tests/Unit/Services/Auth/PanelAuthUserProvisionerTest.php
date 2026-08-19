<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\PanelAuthUserProvisioner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PanelAuthUserProvisionerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.authbridge.jsonrpc.endpoint' => 'https://ufarm-auth.test/api/v1/jsonrpc',
            'services.authbridge.jsonrpc.username' => 'jsonrpc-user',
            'services.authbridge.jsonrpc.password' => 'jsonrpc-secret',
        ]);
    }

    public function test_it_returns_null_and_logs_a_warning_when_jsonrpc_is_not_configured(): void
    {
        config([
            'services.authbridge.jsonrpc.endpoint' => '',
            'services.authbridge.jsonrpc.username' => '',
            'services.authbridge.jsonrpc.password' => '',
        ]);

        Http::fake();
        Log::shouldReceive('warning')->once();

        $authId = (new PanelAuthUserProvisioner)->resolveAuthId('998901234567', 'admin_panel');

        $this->assertNull($authId);
        Http::assertNothingSent();
    }

    public function test_it_resolves_auth_id_and_omits_password_and_entity_id_when_not_given(): void
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => '1',
                'result' => [
                    'success' => true,
                    'user_id' => 12345,
                    'created' => true,
                    'application_alias' => 'admin_panel',
                ],
            ]),
        ]);

        $authId = (new PanelAuthUserProvisioner)->resolveAuthId('998901234567', 'admin_panel');

        $this->assertSame(12345, $authId);

        Http::assertSent(function ($request) {
            $params = $request->data()['params'];

            return $request->data()['method'] === 'findOrCreateUser'
                && $params['phone'] === '998901234567'
                && $params['application_alias'] === 'admin_panel'
                && ! array_key_exists('password', $params)
                && ! array_key_exists('entity_id', $params);
        });
    }

    public function test_it_forwards_password_and_entity_id_when_given(): void
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => '1',
                'result' => ['success' => true, 'user_id' => 777, 'created' => true],
            ]),
        ]);

        $authId = (new PanelAuthUserProvisioner)->resolveAuthId(
            '998901234567',
            'admin_panel',
            password: 'super-secret-1',
            entityId: 42,
        );

        $this->assertSame(777, $authId);

        Http::assertSent(function ($request) {
            $params = $request->data()['params'];

            return $params['password'] === 'super-secret-1'
                && $params['entity_id'] === 42;
        });
    }

    public function test_it_resolves_auth_id_without_password_when_user_already_exists(): void
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => '1',
                'result' => [
                    'success' => true,
                    'user_id' => 555,
                    'created' => false,
                    'was_already_attached' => false,
                ],
            ]),
        ]);

        $authId = (new PanelAuthUserProvisioner)->resolveAuthId(
            '998901234567',
            'admin_panel',
            entityId: 9,
        );

        $this->assertSame(555, $authId);

        Http::assertSent(function ($request) {
            $params = $request->data()['params'];

            return ! array_key_exists('password', $params) && $params['entity_id'] === 9;
        });
    }

    public function test_it_returns_null_and_logs_an_error_on_rpc_failure(): void
    {
        Http::fake([
            '*' => Http::response(['jsonrpc' => '2.0', 'id' => '1', 'error' => ['code' => -32000, 'message' => 'boom']]),
        ]);

        Log::shouldReceive('error')->once();

        $authId = (new PanelAuthUserProvisioner)->resolveAuthId('998901234567', 'admin_panel');

        $this->assertNull($authId);
    }

    public function test_it_returns_null_and_logs_an_error_on_http_failure(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        Log::shouldReceive('error')->once();

        $authId = (new PanelAuthUserProvisioner)->resolveAuthId('998901234567', 'admin_panel');

        $this->assertNull($authId);
    }
}
