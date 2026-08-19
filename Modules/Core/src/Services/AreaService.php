<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\AreaServiceInterface;
use Modules\Core\DTOs\PaginatedAreasData;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;
use Modules\Core\Services\Area\AreaMetricCalculator;

final class AreaService implements AreaServiceInterface
{
    public function paginateUserAreas(User $user, int $perPage): LengthAwarePaginator
    {
        $paginator = $user->areas()
            ->latest()
            ->paginate($perPage);

        return PaginatedAreasData::fromPaginator($paginator)->withAvailableArea();
    }

    public function createArea(User $user, string $name, array $coordinates, ?float $overrideArea): Area
    {
        return DB::transaction(function () use ($user, $name, $coordinates, $overrideArea) {
            $area = new Area;
            $area->user_id = $user->id;
            $area->name = $name;
            $area->coordinates = $coordinates;

            if ($overrideArea !== null) {
                $area->area = $overrideArea;
            }

            $area->save();

            return $area->fresh();
        });
    }

    public function updateArea(Area $area, ?string $name, ?array $coordinates): Area
    {
        return DB::transaction(function () use ($area, $name, $coordinates) {
            if ($name !== null) {
                $area->name = $name;
            }

            if ($coordinates !== null) {
                $area->coordinates = $coordinates;
            }

            $area->save();

            return $area->fresh();
        });
    }

    public function deleteArea(Area $area): void
    {
        DB::transaction(static function () use ($area): void {
            $area->delete();
        });
    }

    public function calculateAreaMetrics(array $coordinates): array
    {
        $areaInSquareMeters = AreaMetricCalculator::calculatePolygonArea($coordinates);
        $hectares = $areaInSquareMeters / AreaMetricCalculator::SQUARE_METERS_PER_HECTARE;
        $isSquareMeterUnit = $areaInSquareMeters < AreaMetricCalculator::SQUARE_METERS_PER_HECTARE;
        $translationKey = $isSquareMeterUnit
            ? CoreTranslationKey::AREA_UNIT_M2->value
            : CoreTranslationKey::AREA_UNIT_HA->value;

        $value = $isSquareMeterUnit ? $areaInSquareMeters : $hectares;
        $formattedValue = number_format($value, AreaMetricCalculator::AREA_PRECISION);
        $formatted = $formattedValue.' '.__($translationKey);

        return [
            'area_m2' => $areaInSquareMeters,
            'area_ha' => $hectares,
            'formatted_area' => $formatted,
        ];
    }

    public function ensureOwnership(Area $area, int $userId): void
    {
        if ($area->user_id !== $userId) {
            throw new AuthorizationException;
        }
    }
}
