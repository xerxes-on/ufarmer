<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionParamSeeder extends Seeder
{
    /**
     * Seed region parameters from CSV data.
     * Data includes pH, carbon, and nitrogen values for all 14 regions.
     */
    public function run(): void
    {
        // Clear existing region params
        DB::table('region_params')->truncate();

        // Region parameter data from /Users/xerxes/Projects/Ufarm/src/src/v3/region_params.csv
        $regionParams = [
            // Tashkent city (region_id=1)
            ['region_id' => 1, 'param_id' => 1, 'value' => '8.1000', 'notes' => 'Sample pH value for Tashkent city'],
            ['region_id' => 1, 'param_id' => 2, 'value' => '1.1000', 'notes' => 'Sample carbon value for Tashkent city'],
            ['region_id' => 1, 'param_id' => 3, 'value' => '2.5000', 'notes' => 'Sample nitrogen value for Tashkent city'],

            // Tashkent region (region_id=2)
            ['region_id' => 2, 'param_id' => 1, 'value' => '5.6000', 'notes' => 'Sample pH value for Tashkent region'],
            ['region_id' => 2, 'param_id' => 2, 'value' => '3.5000', 'notes' => 'Sample carbon value for Tashkent region'],
            ['region_id' => 2, 'param_id' => 3, 'value' => '1.6000', 'notes' => 'Sample nitrogen value for Tashkent region'],

            // Andijan (region_id=3)
            ['region_id' => 3, 'param_id' => 1, 'value' => '7.2000', 'notes' => 'Sample pH value for Andijan region'],
            ['region_id' => 3, 'param_id' => 2, 'value' => '1.2000', 'notes' => 'Sample carbon value for Andijan region'],
            ['region_id' => 3, 'param_id' => 3, 'value' => '1.5000', 'notes' => 'Sample nitrogen value for Andijan region'],

            // Bukhara (region_id=4)
            ['region_id' => 4, 'param_id' => 1, 'value' => '7.8000', 'notes' => 'Sample pH value for Bukhara region'],
            ['region_id' => 4, 'param_id' => 2, 'value' => '0.9000', 'notes' => 'Sample carbon value for Bukhara region'],
            ['region_id' => 4, 'param_id' => 3, 'value' => '1.0000', 'notes' => 'Sample nitrogen value for Bukhara region'],

            // Fergana (region_id=5)
            ['region_id' => 5, 'param_id' => 1, 'value' => '7.5000', 'notes' => 'Sample pH value for Fergana region'],
            ['region_id' => 5, 'param_id' => 2, 'value' => '1.8000', 'notes' => 'Sample carbon value for Fergana region'],
            ['region_id' => 5, 'param_id' => 3, 'value' => '1.4000', 'notes' => 'Sample nitrogen value for Fergana region'],

            // Jizzakh (region_id=6)
            ['region_id' => 6, 'param_id' => 1, 'value' => '7.3000', 'notes' => 'Sample pH value for Jizzakh region'],
            ['region_id' => 6, 'param_id' => 2, 'value' => '1.0000', 'notes' => 'Sample carbon value for Jizzakh region'],
            ['region_id' => 6, 'param_id' => 3, 'value' => '1.2000', 'notes' => 'Sample nitrogen value for Jizzakh region'],

            // Namangan (region_id=7)
            ['region_id' => 7, 'param_id' => 1, 'value' => '7.4000', 'notes' => 'Sample pH value for Namangan region'],
            ['region_id' => 7, 'param_id' => 2, 'value' => '1.6000', 'notes' => 'Sample carbon value for Namangan region'],
            ['region_id' => 7, 'param_id' => 3, 'value' => '1.3000', 'notes' => 'Sample nitrogen value for Namangan region'],

            // Navoiy (region_id=8)
            ['region_id' => 8, 'param_id' => 1, 'value' => '8.0000', 'notes' => 'Sample pH value for Navoiy region'],
            ['region_id' => 8, 'param_id' => 2, 'value' => '0.8000', 'notes' => 'Sample carbon value for Navoiy region'],
            ['region_id' => 8, 'param_id' => 3, 'value' => '0.9000', 'notes' => 'Sample nitrogen value for Navoiy region'],

            // Kashkadarya (region_id=9)
            ['region_id' => 9, 'param_id' => 1, 'value' => '7.6000', 'notes' => 'Sample pH value for Kashkadarya region'],
            ['region_id' => 9, 'param_id' => 2, 'value' => '1.1000', 'notes' => 'Sample carbon value for Kashkadarya region'],
            ['region_id' => 9, 'param_id' => 3, 'value' => '1.1000', 'notes' => 'Sample nitrogen value for Kashkadarya region'],

            // Samarkand (region_id=10)
            ['region_id' => 10, 'param_id' => 1, 'value' => '7.7000', 'notes' => 'Sample pH value for Samarkand region'],
            ['region_id' => 10, 'param_id' => 2, 'value' => '1.5000', 'notes' => 'Sample carbon value for Samarkand region'],
            ['region_id' => 10, 'param_id' => 3, 'value' => '1.4000', 'notes' => 'Sample nitrogen value for Samarkand region'],

            // Sirdarya (region_id=11)
            ['region_id' => 11, 'param_id' => 1, 'value' => '7.9000', 'notes' => 'Sample pH value for Sirdarya region'],
            ['region_id' => 11, 'param_id' => 2, 'value' => '1.3000', 'notes' => 'Sample carbon value for Sirdarya region'],
            ['region_id' => 11, 'param_id' => 3, 'value' => '1.2000', 'notes' => 'Sample nitrogen value for Sirdarya region'],

            // Surkhandarya (region_id=12)
            ['region_id' => 12, 'param_id' => 1, 'value' => '7.5000', 'notes' => 'Sample pH value for Surkhandarya region'],
            ['region_id' => 12, 'param_id' => 2, 'value' => '1.7000', 'notes' => 'Sample carbon value for Surkhandarya region'],
            ['region_id' => 12, 'param_id' => 3, 'value' => '1.6000', 'notes' => 'Sample nitrogen value for Surkhandarya region'],

            // Khorezm (region_id=13)
            ['region_id' => 13, 'param_id' => 1, 'value' => '8.2000', 'notes' => 'Sample pH value for Khorezm region'],
            ['region_id' => 13, 'param_id' => 2, 'value' => '0.7000', 'notes' => 'Sample carbon value for Khorezm region'],
            ['region_id' => 13, 'param_id' => 3, 'value' => '0.8000', 'notes' => 'Sample nitrogen value for Khorezm region'],

            // Karakalpakstan (region_id=14)
            ['region_id' => 14, 'param_id' => 1, 'value' => '8.3000', 'notes' => 'Sample pH value for Karakalpakstan region'],
            ['region_id' => 14, 'param_id' => 2, 'value' => '0.6000', 'notes' => 'Sample carbon value for Karakalpakstan region'],
            ['region_id' => 14, 'param_id' => 3, 'value' => '0.7000', 'notes' => 'Sample nitrogen value for Karakalpakstan region'],
        ];

        // Insert region params with timestamps
        $timestamp = now();
        foreach ($regionParams as &$param) {
            $param['created_at'] = $timestamp;
            $param['updated_at'] = $timestamp;
        }

        DB::table('region_params')->insert($regionParams);

        $this->command->info('✓ Seeded 42 region parameters (pH, carbon, nitrogen for 14 regions)');
    }
}
