<?php

declare(strict_types=1);

namespace Modules\General\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Crops\Models\Crop;
use Modules\General\Models\ProductStat;
use Modules\General\Transformers\CropResource;

class CropPriceHistoryService
{
    /**
     * Get price history for a specific crop
     */
    public function getCropPriceHistory(int $cropId, int $days = 10): array
    {
        // Cache key includes today's date to ensure cache refreshes daily
        $cacheKey = "crop_price_history:{$cropId}:days:{$days}:date:".Carbon::today()->toDateString();

        // Cache for the rest of the day (until midnight)
        $cacheMinutes = Carbon::now()->diffInMinutes(Carbon::tomorrow());

        return Cache::remember($cacheKey, $cacheMinutes, function () use ($cropId, $days) {
            $crop = Crop::findOrFail($cropId);
            $endDate = Carbon::today();
            $startDate = $endDate->copy()->subDays($days - 1);

            // Get product stats for the last N days
            $stats = ProductStat::whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->get();

            $priceHistory = $this->extractCropPricesFromStats($stats, $cropId);
            $processedHistory = $this->processPriceHistory($priceHistory);

            return [
                'crop' => new CropResource($crop),
                'price_history' => $processedHistory,
                'today_status' => $this->getTodayStatus($processedHistory),
            ];
        });
    }

    /**
     * Extract crop prices from product stats
     */
    private function extractCropPricesFromStats(Collection $stats, int $cropId): array
    {
        $priceHistory = [];

        foreach ($stats as $stat) {
            $date = $stat->date->format('Y-m-d');
            $data = $stat->data ?? [];

            // Look for the crop in the products array
            $cropPrice = null;
            if (isset($data['products']) && is_array($data['products'])) {
                foreach ($data['products'] as $product) {
                    if (isset($product['crop_id']) && $product['crop_id'] === $cropId) {
                        $cropPrice = $product['price'] ?? null;
                        break;
                    }
                }
            }

            // For demo purposes, generate random prices if not found
            if ($cropPrice === null) {
                $cropPrice = $this->generateDemoPrice($cropId, $date);
            }

            $priceHistory[] = [
                'date' => $date,
                'price' => $cropPrice,
            ];
        }

        return $priceHistory;
    }

    /**
     * Process price history to calculate trends and percentages
     */
    private function processPriceHistory(array $priceHistory): array
    {
        $processed = [];

        for ($i = 0; $i < count($priceHistory); $i++) {
            $current = $priceHistory[$i];
            $previous = $i > 0 ? $priceHistory[$i - 1] : null;

            $trend = 'stable';
            $changePercentage = 0;

            if ($previous && $previous['price'] > 0) {
                $priceDiff = $current['price'] - $previous['price'];
                $changePercentage = round(($priceDiff / $previous['price']) * 100, 2);

                if ($changePercentage > 0) {
                    $trend = 'up';
                } elseif ($changePercentage < 0) {
                    $trend = 'down';
                }
            }

            $processed[] = [
                'date' => $current['date'],
                'price' => $current['price'],
                'trend' => $trend,
                'change_percentage' => $changePercentage,
                'is_today' => $current['date'] === Carbon::today()->format('Y-m-d'),
            ];
        }

        return $processed;
    }

    /**
     * Get today's status with multilingual tags
     */
    private function getTodayStatus(array $processedHistory): array
    {
        $today = collect($processedHistory)->firstWhere('is_today', true);

        if (! $today) {
            return [
                'trend' => 'unknown',
                'tag' => [
                    'uz' => 'Noma\'lum',
                    'ru' => 'Неизвестно',
                    'en' => 'Unknown',
                ],
            ];
        }

        $tags = [
            'up' => [
                'uz' => 'Qimmat',
                'ru' => 'Дорого',
                'en' => 'Expensive',
            ],
            'down' => [
                'uz' => 'Arzon',
                'ru' => 'Дешево',
                'en' => 'Cheap',
            ],
            'stable' => [
                'uz' => 'Barqaror',
                'ru' => 'Стабильно',
                'en' => 'Stable',
            ],
        ];

        return [
            'trend' => $today['trend'],
            'tag' => $tags[$today['trend']] ?? $tags['stable'],
        ];
    }

    /**
     * Format crop details for response
     */
    private function formatCropDetails(Crop $crop): array
    {
        return [
            'id' => $crop->id,
            'name' => $crop->name,
            'attributes' => $crop->only(['type', 'category', 'season']),
        ];
    }

    /**
     * Generate demo price for testing
     */
    private function generateDemoPrice(int $cropId, string $date): float
    {
        // Generate consistent demo prices based on crop ID and date
        $basePrice = 1000 + ($cropId * 500);
        $dateValue = strtotime($date);
        $variation = sin($dateValue / 86400) * 200; // Daily variation

        return round($basePrice + $variation, 2);
    }
}
