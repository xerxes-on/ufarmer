<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface HttpClientInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function get(string $url, array $options = []): array;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function post(string $url, array $data = [], array $options = []): array;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function put(string $url, array $data = [], array $options = []): array;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function delete(string $url, array $options = []): array;

    public function withBasicAuth(string $username, string $password): static;

    public function withToken(string $token): static;

    public function timeout(int $seconds): static;
}
