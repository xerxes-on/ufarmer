<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final class PanelAuthUserProvisioner
{
    /**
     * Resolve the AuthBridge user id for a phone number via the
     * `findOrCreateUser` JSON-RPC method, lazily creating (and attaching to
     * the given application) an SSO account if one doesn't already exist.
     *
     * Never touches an existing user's password — `$password` is only ever
     * used by `findOrCreateUser` when it creates a brand-new SSO account for
     * this phone. Omitted from the RPC params altogether when null/empty,
     * rather than sending an empty string.
     *
     * `$entityId` should always be this app's own local `users.id` for the
     * employee being resolved. Omitted from the RPC params when null.
     *
     * Returns null (and logs) when the lookup can't be resolved, rather than
     * throwing, so saving an employee never hard-fails on an external RPC
     * hiccup or missing configuration — a blank `auth_id` is a supported
     * "links up automatically on first SSO login" state.
     */
    public function resolveAuthId(
        string $phone,
        string $applicationAlias,
        ?string $password = null,
        ?int $entityId = null,
    ): ?int {
        if (! $this->isConfigured()) {
            Log::warning('Auth id resolution skipped: JSON-RPC is not configured', [
                'phone' => $phone,
                'application_alias' => $applicationAlias,
            ]);

            return null;
        }

        $params = [
            'phone' => $phone,
            'application_alias' => $applicationAlias,
        ];

        if (filled($password)) {
            $params['password'] = $password;
        }

        if ($entityId !== null) {
            $params['entity_id'] = $entityId;
        }

        try {
            $result = $this->call('findOrCreateUser', $params);
        } catch (RuntimeException $exception) {
            Log::error('Auth id resolution failed', [
                'phone' => $phone,
                'application_alias' => $applicationAlias,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return isset($result['user_id']) ? (int) $result['user_id'] : null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function call(string $method, array $params): array
    {
        $response = Http::withBasicAuth($this->username(), $this->password())
            ->timeout(10)
            ->post($this->endpoint(), [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => (string) Str::uuid(),
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Auth JSON-RPC {$method} failed");
        }

        $payload = $response->json();

        if (! is_array($payload) || isset($payload['error'])) {
            throw new RuntimeException("Auth JSON-RPC {$method} returned an error");
        }

        $result = $payload['result'] ?? null;

        if (! is_array($result)) {
            throw new RuntimeException("Auth JSON-RPC {$method} returned an invalid result");
        }

        return $result;
    }

    private function isConfigured(): bool
    {
        return $this->endpoint() !== '' && $this->username() !== '' && $this->password() !== '';
    }

    private function endpoint(): string
    {
        return (string) config('services.authbridge.jsonrpc.endpoint');
    }

    private function username(): string
    {
        return (string) config('services.authbridge.jsonrpc.username');
    }

    private function password(): string
    {
        return (string) config('services.authbridge.jsonrpc.password');
    }
}
