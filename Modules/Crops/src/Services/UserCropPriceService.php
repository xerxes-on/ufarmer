<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Models\Crop;
use Throwable;

final class UserCropPriceService
{
    public function paginateUserCropsWithPrices(User $user, int $perPage): LengthAwarePaginator
    {
        $paginator = $user->crops()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name->en')
            ->paginate($perPage);

        $ruNames = $paginator->getCollection()
            ->map(fn (Crop $crop) => $crop->getTranslation('name', 'ru'))
            ->filter()
            ->map(fn (?string $name) => preg_replace('/\s*\(.*?\)\s*/', ' ', $name))
            ->map(fn (?string $name) => trim(preg_replace('/\s+/', ' ', $name)))
            ->unique()
            ->values()
            ->all();

        $prices = $this->fetchPricesFromExternalApi($ruNames);

        $paginator->getCollection()->transform(function (Crop $crop) use ($prices) {
            $data = $crop->toArray();
            $ruName = $crop->getTranslation('name', 'ru');

            $priceInfo = null;
            if ($ruName && $prices !== null) {
                foreach ($prices as $item) {
                    if (($item['search_term'] ?? null) === $ruName || ($item['name'] ?? null) === $ruName) {
                        $priceInfo = $item;
                        break;
                    }
                }
            }

            $data['price_data'] = [
                'today' => $priceInfo['today'] ?? [],
                'week' => $priceInfo['week'] ?? [],
            ];

            return $data;
        });

        return $paginator;
    }

    public function userCropPriceHistory(User $user, string $cropId, int $page, int $perPage): array
    {
        $crop = $user->crops()
            ->where('crops.id', $cropId)
            ->first();

        if (! $crop) {
            throw new CropsException(CropsTranslationKey::USER_CROP_NOT_FOUND->value, 404);
        }

        $ruName = $crop->getTranslation('name', 'ru');

        if (! $ruName) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 422, 'Crop lacks Russian name');
        }

        $cleanedName = preg_replace('/\s*\(.*?\)\s*/', ' ', $ruName);
        $cleanedName = trim(preg_replace('/\s+/', ' ', $cleanedName));

        $weeklyData = $this->fetchPricesFromExternalApi([$cleanedName]);

        if (empty($weeklyData) || empty($weeklyData[0]['today'])) {
            return $this->emptyPriceResponse($cropId, $crop->name, $crop->image, $perPage);
        }

        $productUuid = $weeklyData[0]['today'][0]['product_uuid'] ?? null;

        if (! $productUuid) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 404, 'Product UUID missing');
        }

        return $this->fetchPaginatedPriceHistory($productUuid, $page, $perPage);
    }

    private function fetchPricesFromExternalApi(array $names): ?array
    {
        if ($names === []) {
            return [];
        }

        $url = config('services.crops_price.url');
        $user = config('services.crops_price.user');
        $pass = config('services.crops_price.pass');

        if (! $url || ! $user || ! $pass) {
            Log::warning('External crop price API not configured properly.', [
                'url' => $url,
                'has_user' => ! empty($user),
                'has_pass' => ! empty($pass),
            ]);

            return null;
        }

        $cacheKey = 'user_crops_prices:'.md5(json_encode($names));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($names, $url, $user, $pass) {
            try {
                $searchUrl = str_replace('/api/products/prices', '/api/products/price-search', $url);

                $response = Http::timeout(10)
                    ->acceptJson()
                    ->withBasicAuth($user, $pass)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($searchUrl, ['names' => $names]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['data'] ?? [];
                }

                Log::warning('Failed to fetch crop prices from external API.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (Throwable $exception) {
                Log::error('Exception while fetching crop prices.', [
                    'exception' => $exception->getMessage(),
                ]);
            }

            return null;
        });
    }

    private function fetchPaginatedPriceHistory(string $productUuid, int $page, int $perPage): array
    {
        $url = config('services.crops_price.url');
        $user = config('services.crops_price.user');
        $pass = config('services.crops_price.pass');

        if (! $url || ! $user || ! $pass) {
            return $this->emptyPriceResponse(null, null, null, $perPage);
        }

        $cacheKey = sprintf('product_price_history:%s:page:%d:per:%d', $productUuid, $page, $perPage);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($url, $user, $pass, $productUuid, $page, $perPage) {
            try {
                $priceUrl = str_replace('/api/products/prices', "/api/products/{$productUuid}/prices", $url);

                $response = Http::timeout(10)
                    ->acceptJson()
                    ->withBasicAuth($user, $pass)
                    ->get($priceUrl, [
                        'page' => $page,
                        'per_page' => $perPage,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Failed to fetch product price history.', [
                    'product_uuid' => $productUuid,
                    'status' => $response->status(),
                ]);
            } catch (Throwable $exception) {
                Log::error('Exception while fetching product price history.', [
                    'product_uuid' => $productUuid,
                    'exception' => $exception->getMessage(),
                ]);
            }

            return $this->emptyPriceResponse(null, null, null, $perPage);
        });
    }

    private function emptyPriceResponse(?string $uuid, $name, $image, int $perPage): array
    {
        return [
            'product' => [
                'uuid' => $uuid,
                'name' => $name,
                'image' => $image,
            ],
            'prices' => [],
            'meta' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
            ],
        ];
    }
}
