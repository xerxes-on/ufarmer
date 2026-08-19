<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Crops\Models\Crop;
use Throwable;

final class CropPriceService
{
    public function fetchExternalPrices(LengthAwarePaginator $paginator): array
    {
        $url = config('services.crops_price.url');
        $user = config('services.crops_price.user');
        $pass = config('services.crops_price.pass');

        $ruNames = $paginator->getCollection()
            ->map(fn (Crop $crop) => $crop->getTranslation('name', 'ru'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $url || $ruNames === []) {
            return [];
        }

        if (! $user || ! $pass) {
            Log::warning('External crop price fetch skipped because credentials are missing.', ['url' => $url]);

            return [];
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->withBasicAuth($user, $pass)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, ['names' => $ruNames]);

            $json = $response->json();

            if (! $response->successful() || ! is_array($json) || ! isset($json['prices']) || ! is_array($json['prices'])) {
                Log::warning('External crop price response did not contain expected data.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $prices = [];
            foreach ($json['prices'] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $name = $entry['nameRu']
                    ?? $entry['name']
                    ?? $entry['search_term']
                    ?? null;

                if (! $name) {
                    continue;
                }

                $prices[$name] = $entry;
            }

            return $prices;
        } catch (Throwable $exception) {
            Log::error('Failed to fetch external crop prices.', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
