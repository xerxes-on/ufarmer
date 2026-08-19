<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Contracts\CacheServiceInterface;

final class CacheService implements CacheServiceInterface
{
    /** @var array<string>|null */
    private ?array $tags = null;

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return $this->getStore()->remember($key, $ttl, $callback);
    }

    public function forget(string $key): bool
    {
        return $this->getStore()->forget($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getStore()->get($key, $default);
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        return $this->getStore()->put($key, $value, $ttl);
    }

    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->getStore()->increment($key, $value);
    }

    public function tags(array $tags): static
    {
        $clone = clone $this;
        $clone->tags = $tags;

        return $clone;
    }

    public function flush(): bool
    {
        return $this->getStore()->flush();
    }

    private function getStore(): mixed
    {
        if ($this->tags !== null) {
            return Cache::tags($this->tags);
        }

        return Cache::store();
    }
}
