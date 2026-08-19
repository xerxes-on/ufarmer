<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Contracts\RegionServiceInterface;
use Modules\Core\Models\Region;

final class RegionService implements RegionServiceInterface
{
    public function listRegions(bool $withCities): array
    {
        $version = Cache::get('regions_version', 1);
        $locale = app()->getLocale();
        $cacheKey = sprintf(
            'regions:index:v%s:with_cities:%s:loc:%s',
            $version,
            $withCities ? '1' : '0',
            $locale
        );

        return Cache::rememberForever($cacheKey, function () use ($withCities) {
            $query = Region::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name->en');

            if ($withCities) {
                $query->with(['cities' => function ($query): void {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name->en');
                }]);
            }

            return $query->get()->toArray();
        });
    }

    public function getRegion(Region $region, bool $withCities = true): Region
    {
        if (! $region->is_active) {
            $exception = new ModelNotFoundException;
            $exception->setModel(Region::class, [$region->getKey()]);

            throw $exception;
        }

        if ($withCities) {
            $region->load('cities');
        }

        return $region;
    }

    public function listRegionCities(Region $region): Collection
    {
        $activeRegion = $this->getRegion($region, false);
        $version = Cache::get('regions_version', 1);
        $locale = app()->getLocale();
        $cacheKey = sprintf(
            'regions:%d:cities:v%s:loc:%s',
            $activeRegion->getKey(),
            $version,
            $locale
        );

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($activeRegion) {
            return $activeRegion->cities()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name->en')
                ->get();
        });
    }
}
