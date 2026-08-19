<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Database\Seeders\CropsSeeder;
use Database\Seeders\RegionsAndCitiesSeeder;
use Illuminate\Support\Facades\DB;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Throwable;

final class SeederService
{
    public function seedRegionsAndCities(): array
    {
        $existingRegions = DB::table('regions')->count();

        if ($existingRegions > 0) {
            throw new CropsException(
                CropsTranslationKey::SEEDER_REGIONS_ALREADY_SEEDED->value,
                409
            );
        }

        try {
            (new RegionsAndCitiesSeeder)->run();
        } catch (Throwable $exception) {
            throw new CropsException(
                CropsTranslationKey::SEEDER_REGIONS_FAILED->value,
                500,
                previous: $exception
            );
        }

        return [
            'regions_created' => DB::table('regions')->count(),
            'cities_created' => DB::table('cities')->count(),
        ];
    }

    public function clearRegionsAndCities(): void
    {
        try {
            DB::transaction(static function (): void {
                DB::table('cities')->delete();
                DB::table('regions')->delete();
            });
        } catch (Throwable $exception) {
            throw new CropsException(
                CropsTranslationKey::SEEDER_REGIONS_CLEAR_FAILED->value,
                500,
                previous: $exception
            );
        }
    }

    public function regionsCitiesStats(): array
    {
        $stats = [
            'total_regions' => DB::table('regions')->count(),
            'total_cities' => DB::table('cities')->count(),
            'regions_with_cities' => DB::table('regions')
                ->selectRaw('regions.*, COUNT(cities.id) as city_count')
                ->leftJoin('cities', 'regions.id', '=', 'cities.region_id')
                ->groupBy('regions.id')
                ->orderBy('city_count', 'desc')
                ->limit(10)
                ->get()
                ->map(static function ($region) {
                    return [
                        'id' => $region->id,
                        'name' => json_decode($region->name, true),
                        'city_count' => (int) $region->city_count,
                    ];
                })
                ->values(),
        ];

        return $stats;
    }

    public function seedCrops(): array
    {
        $existingCrops = DB::table('crops')->count();

        if ($existingCrops > 0) {
            throw new CropsException(
                CropsTranslationKey::SEEDER_CROPS_ALREADY_SEEDED->value,
                409
            );
        }

        try {
            (new CropsSeeder)->run();
        } catch (Throwable $exception) {
            throw new CropsException(
                CropsTranslationKey::SEEDER_CROPS_FAILED->value,
                500,
                previous: $exception
            );
        }

        return [
            'crops_created' => DB::table('crops')->count(),
        ];
    }

    public function clearCrops(): void
    {
        try {
            DB::transaction(static function (): void {
                DB::table('user_crops')->delete();
                DB::table('crops')->delete();
            });
        } catch (Throwable $exception) {
            throw new CropsException(
                CropsTranslationKey::SEEDER_CROPS_CLEAR_FAILED->value,
                500,
                previous: $exception
            );
        }
    }
}
