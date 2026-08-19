<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class RealIpMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $this->resolveClientIp($request);

        if ($ip !== null) {
            foreach ($this->serverKeys() as $key) {
                $request->server->set($key, $ip);
                $_SERVER[$key] = $ip;
            }
        }

        return $next($request);
    }

    private function resolveClientIp(Request $request): ?string
    {
        if (! $this->isFromTrustedProxy($request)) {
            return null;
        }

        foreach ($this->headerNames() as $header) {
            if (strtolower($header) === 'x-forwarded-for') {
                $ip = $this->resolveForwardedFor($request->headers->get($header));
            } else {
                $ip = $this->normalizeIp($request->headers->get($header));
            }

            if ($ip !== null) {
                return $ip;
            }
        }

        return null;
    }

    private function normalizeIp(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_IP) ?: null;
    }

    private function resolveForwardedFor(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $value));
        $index = $this->forwardedForIndex();
        $offset = $index < 0 ? count($parts) + $index : $index;

        if (isset($parts[$offset])) {
            $ip = $this->normalizeIp($parts[$offset]);
            if ($ip !== null) {
                return $ip;
            }
        }

        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $ip = $this->normalizeIp($parts[$i]);
            if ($ip !== null) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function headerNames(): array
    {
        $headers = config('real-ip.headers', []);

        return array_values(array_filter(array_map('trim', $headers), static fn ($value) => $value !== ''));
    }

    /**
     * @return array<int, string>
     */
    private function serverKeys(): array
    {
        $keys = config('real-ip.server_keys', ['REMOTE_ADDR']);

        return array_values(array_filter(array_map('trim', $keys), static fn ($value) => $value !== ''));
    }

    private function forwardedForIndex(): int
    {
        return (int) config('real-ip.forwarded_for_index', -1);
    }

    private function isFromTrustedProxy(Request $request): bool
    {
        $peer = $request->server->get('REMOTE_ADDR');
        $trusted = $this->trustedProxies();

        return $peer !== null && $trusted !== [] && IpUtils::checkIp($peer, $trusted);
    }

    /**
     * @return array<int, string>
     */
    private function trustedProxies(): array
    {
        return config('real-ip.trusted_proxies', []);
    }
}
