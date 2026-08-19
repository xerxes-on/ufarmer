<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Models\Region;

interface RegionServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRegions(bool $withCities): array;

    public function getRegion(Region $region, bool $withCities = true): Region;

    public function listRegionCities(Region $region): Collection;
}
