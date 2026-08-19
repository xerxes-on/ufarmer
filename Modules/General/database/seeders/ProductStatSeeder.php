<?php

declare(strict_types=1);

namespace Modules\General\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Crops\Models\Crop;
use Modules\General\Models\ProductStat;

class ProductStatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all crops
        $crops = Crop::all();

        if ($crops->isEmpty()) {
            $this->command->warn('No crops found. Please seed crops first.');

            return;
        }

        // Generate stats for the last 15 days
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(14);

        // Base prices for different crop types (in sum)
        $basePrices = [
            'cotton' => 15000,
            'wheat' => 8000,
            'corn' => 6000,
            'rice' => 12000,
            'vegetables' => 5000,
            'fruits' => 10000,
            'default' => 7000,
        ];

        // Generate data for each day
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $products = [];

            foreach ($crops as $crop) {
                // Determine base price based on crop name
                $basePrice = $basePrices['default'];
                $cropNameLower = strtolower($crop->title_uz ?? '');

                foreach ($basePrices as $type => $price) {
                    if (str_contains($cropNameLower, $type)) {
                        $basePrice = $price;
                        break;
                    }
                }

                // Add daily variation (±5% random change)
                $dailyVariation = (rand(-500, 500) / 10000) * $basePrice;

                // Add seasonal variation based on date
                $dayOfYear = $date->dayOfYear;
                $seasonalVariation = sin($dayOfYear * 2 * pi() / 365) * $basePrice * 0.1;

                // Add trend (slight upward trend over time)
                $trendFactor = ($date->diffInDays($startDate) / 365) * $basePrice * 0.05;

                $finalPrice = round($basePrice + $dailyVariation + $seasonalVariation + $trendFactor, 2);

                $products[] = [
                    'crop_id' => $crop->id,
                    'name' => $crop->title_uz,
                    'price' => $finalPrice,
                    'currency' => 'UZS',
                    'unit' => 'kg',
                    'stock' => rand(100, 10000),
                    'category' => $this->getCropCategory($crop),
                ];
            }

            // Create or update product stat for this date
            ProductStat::updateOrCreate(
                ['date' => $date->toDateString()],
                [
                    'data' => [
                        'products' => $products,
                        'timestamp' => $date->toISOString(),
                        'source' => 'seeder',
                        'market' => 'Uzbekistan Agricultural Exchange',
                    ],
                ]
            );

            $this->command->info("Created product stats for {$date->toDateString()}");
        }

        $this->command->info('Product stats seeded successfully for 15 days!');
    }

    /**
     * Determine crop category based on name
     */
    private function getCropCategory($crop): string
    {
        $name = strtolower($crop->title_uz ?? '');

        if (str_contains($name, 'paxta') || str_contains($name, 'cotton')) {
            return 'fiber';
        } elseif (str_contains($name, 'bug\'doy') || str_contains($name, 'wheat')) {
            return 'grain';
        } elseif (str_contains($name, 'sabzavot') || str_contains($name, 'vegetable')) {
            return 'vegetable';
        } elseif (str_contains($name, 'meva') || str_contains($name, 'fruit')) {
            return 'fruit';
        } elseif (str_contains($name, 'makka') || str_contains($name, 'corn')) {
            return 'grain';
        } elseif (str_contains($name, 'sholi') || str_contains($name, 'rice')) {
            return 'grain';
        }

        return 'other';
    }
}
