<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\AgroPrices\Enums\PriceStatus;
use Modules\AgroPrices\Models\DailyPrice;
use Modules\AgroPrices\Models\Product;

class DailyPricesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $products = Product::query()->orderBy('id')->get();

        // Seed yesterday's prices (baseline)
        foreach ($products as $i => $product) {
            $base = $this->basePriceForIndex($i);

            DailyPrice::query()->updateOrCreate(
                ['product_id' => $product->id, 'date' => $yesterday->toDateString()],
                ['price' => $base, 'status' => PriceStatus::Stable->value]
            );

            // Calculate today's change
            $delta = $this->deltaForIndex($i);
            $todayPrice = round($base + $delta, 2);
            $status = $delta > 0 ? PriceStatus::Up : ($delta < 0 ? PriceStatus::Down : PriceStatus::Stable);

            DailyPrice::query()->updateOrCreate(
                ['product_id' => $product->id, 'date' => $today->toDateString()],
                ['price' => $todayPrice, 'status' => $status->value]
            );
        }
    }

    private function basePriceForIndex(int $i): float
    {
        // Spread baseline prices roughly between 20,000 and 2,000,000 UZS
        $bands = [20000, 45000, 65000, 90000, 120000, 150000, 200000, 260000, 320000, 400000, 520000, 650000, 800000, 1000000, 1200000, 1500000, 1800000, 2000000];

        return (float) ($bands[$i % count($bands)] + (($i * 137) % 900));
    }

    private function deltaForIndex(int $i): float
    {
        // Deterministic small change for reproducibility
        $pattern = [
            1500, 0, -1000, 2500, -500, 0, 1200, -800, 0, 1800,
            -1200, 0, 900, -700, 0, 2200, -300, 0, 1600, -1400,
            0, 600, -500, 0, 1300, -900, 0, 1700, -1100, 0,
        ];

        return (float) ($pattern[$i % count($pattern)]);
    }
}
