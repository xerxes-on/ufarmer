<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Closure;

interface CacheServiceInterface
{
    public function remember(string $key, int $ttl, Closure $callback): mixed;

    public function forget(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value, int $ttl): bool;

    public function increment(string $key, int $value = 1): int|bool;

    public function tags(array $tags): static;

    public function flush(): bool;
}
