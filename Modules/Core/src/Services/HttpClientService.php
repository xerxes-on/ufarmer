<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Modules\Core\Contracts\HttpClientInterface;

final class HttpClientService implements HttpClientInterface
{
    private ?string $username = null;

    private ?string $password = null;

    private ?string $token = null;

    private int $timeout = 30;

    public function get(string $url, array $options = []): array
    {
        $response = $this->buildRequest($options)->get($url);

        return $this->processResponse($response);
    }

    public function post(string $url, array $data = [], array $options = []): array
    {
        $response = $this->buildRequest($options)->post($url, $data);

        return $this->processResponse($response);
    }

    public function put(string $url, array $data = [], array $options = []): array
    {
        $response = $this->buildRequest($options)->put($url, $data);

        return $this->processResponse($response);
    }

    public function delete(string $url, array $options = []): array
    {
        $response = $this->buildRequest($options)->delete($url);

        return $this->processResponse($response);
    }

    public function withBasicAuth(string $username, string $password): static
    {
        $clone = clone $this;
        $clone->username = $username;
        $clone->password = $password;

        return $clone;
    }

    public function withToken(string $token): static
    {
        $clone = clone $this;
        $clone->token = $token;

        return $clone;
    }

    public function timeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->timeout = $seconds;

        return $clone;
    }

    private function buildRequest(array $options): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout($this->timeout)->acceptJson();

        if ($this->username !== null && $this->password !== null) {
            $request = $request->withBasicAuth($this->username, $this->password);
        }

        if ($this->token !== null) {
            $request = $request->withToken($this->token);
        }

        if (isset($options['headers']) && is_array($options['headers'])) {
            $request = $request->withHeaders($options['headers']);
        }

        return $request;
    }

    private function processResponse(\Illuminate\Http\Client\Response $response): array
    {
        return [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'data' => $response->json() ?? [],
            'body' => $response->body(),
        ];
    }
}
