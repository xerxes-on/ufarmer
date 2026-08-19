<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageFetchingService
{
    protected string $unsplashApiKey;

    protected string $pexelsApiKey;

    protected int $unsplashRequestsUsed = 0;

    protected int $pexelsRequestsUsed = 0;

    public function __construct()
    {
        $this->unsplashApiKey = config('services.unsplash.access_key');
        $this->pexelsApiKey = 'X4mbiTORGiTlWxf6Lmw8AVvl0bgwLYwWnAKA1zTpBYMyzg2GYEmbLzDg';
    }

    public function fetchImages(string $query, int $count = 3, ?string $preferredApi = null): array
    {
        $images = [];

        // Try preferred API first if specified
        if ($preferredApi === 'unsplash' && $this->unsplashRequestsUsed < 50) {
            $images = $this->fetchFromUnsplash($query, $count);
        } elseif ($preferredApi === 'pexels') {
            $images = $this->fetchFromPexels($query, $count);
        }

        // If not enough images, try the other API
        $remaining = $count - count($images);
        if ($remaining > 0) {
            if ($this->unsplashRequestsUsed < 50 && $preferredApi !== 'unsplash') {
                $images = array_merge($images, $this->fetchFromUnsplash($query, $remaining));
            }

            $remaining = $count - count($images);
            if ($remaining > 0 && $preferredApi !== 'pexels') {
                $images = array_merge($images, $this->fetchFromPexels($query, $remaining));
            }
        }

        return array_slice($images, 0, $count);
    }

    protected function fetchFromUnsplash(string $query, int $count): array
    {
        try {
            $response = Http::get('https://api.unsplash.com/search/photos', [
                'client_id' => $this->unsplashApiKey,
                'query' => $query,
                'per_page' => $count,
                'orientation' => 'landscape',
            ]);

            if ($response->successful()) {
                $this->unsplashRequestsUsed++;
                $data = $response->json();

                return array_map(function ($photo) {
                    return $photo['urls']['regular'] ?? $photo['urls']['full'];
                }, $data['results'] ?? []);
            }

            Log::warning('Unsplash API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Exception $e) {
            Log::error('Unsplash API error', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
        }

        return [];
    }

    protected function fetchFromPexels(string $query, int $count): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->pexelsApiKey,
            ])->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'per_page' => $count,
                'orientation' => 'landscape',
            ]);

            if ($response->successful()) {
                $this->pexelsRequestsUsed++;

                // Check rate limit headers
                $rateLimit = $response->header('X-Ratelimit-Limit');
                $rateLimitRemaining = $response->header('X-Ratelimit-Remaining');

                Log::info('Pexels API rate limit', [
                    'limit' => $rateLimit,
                    'remaining' => $rateLimitRemaining,
                ]);

                $data = $response->json();

                return array_map(function ($photo) {
                    return $photo['src']['large'] ?? $photo['src']['original'];
                }, $data['photos'] ?? []);
            }

            Log::warning('Pexels API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Exception $e) {
            Log::error('Pexels API error', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
        }

        return [];
    }

    public function getUnsplashRequestsUsed(): int
    {
        return $this->unsplashRequestsUsed;
    }

    public function getPexelsRequestsUsed(): int
    {
        return $this->pexelsRequestsUsed;
    }
}
