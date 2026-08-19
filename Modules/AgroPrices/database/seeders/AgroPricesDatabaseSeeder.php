<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Database\Seeders;

use Illuminate\Database\Seeder;

class AgroPricesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ProductsTableSeeder::class,
            DailyPricesTableSeeder::class,
        ]);
    }
}
