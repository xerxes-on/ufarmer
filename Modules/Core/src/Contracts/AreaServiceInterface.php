<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;

interface AreaServiceInterface
{
    public function paginateUserAreas(User $user, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<array{lat: float, lng: float}>  $coordinates
     */
    public function createArea(User $user, string $name, array $coordinates, ?float $overrideArea): Area;

    /**
     * @param  array<array{lat: float, lng: float}>|null  $coordinates
     */
    public function updateArea(Area $area, ?string $name, ?array $coordinates): Area;

    public function deleteArea(Area $area): void;

    /**
     * @param  array<array{lat: float, lng: float}>  $coordinates
     * @return array{area_m2: float, area_ha: float, formatted_area: string}
     */
    public function calculateAreaMetrics(array $coordinates): array;

    public function ensureOwnership(Area $area, int $userId): void;
}
