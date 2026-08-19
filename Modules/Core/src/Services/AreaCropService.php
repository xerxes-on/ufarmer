<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Exceptions\CoreException;
use Modules\Core\Models\Area;
use Modules\Core\Models\AreaCrop;
use Modules\Crops\Models\Crop;

final class AreaCropService
{
    public function paginateAreaCrops(Area $area, bool $includeInactive, int $perPage): LengthAwarePaginator
    {
        $query = $includeInactive ? $area->crops() : $area->activeCrops();

        return $query
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name->en')
            ->paginate($perPage);
    }

    /**
     * @return array{crop: Crop, area_crop_id: int}
     */
    public function attachCrop(Area $area, ?string $cropUuid, ?int $cropId, ?float $areaValue, ?string $dateStarted, ?string $expectedHarvestDate = null, ?int $userId = null): array
    {
        $crop = $this->resolveActiveCrop($cropUuid, $cropId);
        $resolvedArea = $areaValue ?? (float) $area->area;

        $this->validateAreaAllocation($area, $resolvedArea);

        DB::transaction(static function () use ($area, $crop, $resolvedArea, $dateStarted, $expectedHarvestDate): void {
            $area->crops()->syncWithoutDetaching([
                $crop->id => [
                    'area' => $resolvedArea,
                    'date_started' => $dateStarted,
                    'expected_harvest_date' => $expectedHarvestDate,
                    'active' => true,
                ],
            ]);
        });

        $areaCrop = AreaCrop::where('area_id', $area->id)
            ->where('crop_id', $crop->id)
            ->first();

        if ($userId !== null) {
            $this->createUserCrop($userId, $crop->id, $areaCrop->id);
        }

        return [
            'crop' => $this->getAttachedCrop($area, $crop->id),
            'area_crop_id' => $areaCrop->id,
        ];
    }

    public function updateAreaCrop(Area $area, string $cropUuid, ?float $areaValue, ?string $dateStarted, ?string $expectedHarvestDate = null): Crop
    {
        $crop = Crop::where('uuid', $cropUuid)->first();

        if (! $crop) {
            $this->throwCropNotFound();
        }

        if (! $area->crops()->where('crops.id', $crop->id)->exists()) {
            $this->throwAreaCropNotFound();
        }

        $areaCrop = AreaCrop::where('area_id', $area->id)
            ->where('crop_id', $crop->id)
            ->first();

        if ($areaValue !== null && $areaCrop) {
            $this->validateAreaAllocation($area, $areaValue, $areaCrop->id);
        }

        DB::transaction(static function () use ($area, $crop, $areaValue, $dateStarted, $expectedHarvestDate): void {
            $payload = [];

            if ($areaValue !== null) {
                $payload['area'] = $areaValue;
            }

            if ($dateStarted !== null) {
                $payload['date_started'] = $dateStarted;
            }

            if ($expectedHarvestDate !== null) {
                $payload['expected_harvest_date'] = $expectedHarvestDate;
            }

            if (! empty($payload)) {
                $area->crops()->updateExistingPivot($crop->id, $payload);
            }
        });

        return $this->getAttachedCrop($area, $crop->id);
    }

    public function detachCrop(Area $area, string $cropUuid): void
    {
        $crop = Crop::where('uuid', $cropUuid)->first();

        if (! $crop) {
            $this->throwCropNotFound();
        }

        if (! $area->crops()->where('crops.id', $crop->id)->exists()) {
            $this->throwAreaCropNotFound();
        }

        DB::transaction(static function () use ($area, $crop): void {
            $area->crops()->detach($crop->id);
        });
    }

    /**
     * @return array{crops: EloquentCollection, area_crop_ids: array<int, int>}
     */
    public function attachMultiple(Area $area, array $items, ?int $userId = null): array
    {
        $map = [];

        foreach ($items as $row) {
            $crop = $this->resolveActiveCrop($row['crop_uuid'] ?? null, $row['crop_id'] ?? null, false);

            if (! $crop) {
                continue;
            }

            $map[$crop->id] = [
                'area' => isset($row['area']) ? (float) $row['area'] : (float) $area->area,
                'date_started' => $row['date_started'] ?? null,
                'active' => true,
            ];
        }

        if ($map === []) {
            $this->throwNoValidCrops();
        }

        DB::transaction(static function () use ($area, $map): void {
            $area->crops()->syncWithoutDetaching($map);
        });

        $areaCrops = AreaCrop::where('area_id', $area->id)
            ->whereIn('crop_id', array_keys($map))
            ->pluck('id', 'crop_id')
            ->all();

        if ($userId !== null) {
            $this->createUserCropsFromMap($userId, $areaCrops);
        }

        $crops = $area->crops()
            ->whereIn('crops.id', array_keys($map))
            ->orderBy('sort_order')
            ->orderBy('name->en')
            ->get();

        return [
            'crops' => $crops,
            'area_crop_ids' => $areaCrops,
        ];
    }

    public function toggleAreaCrop(Area $area, string $cropUuid, int $userId): AreaCrop
    {
        $crop = Crop::where('uuid', $cropUuid)->first();

        if (! $crop) {
            $this->throwCropNotFound();
        }

        $areaCrop = AreaCrop::where('area_id', $area->id)
            ->where('crop_id', $crop->id)
            ->where('user_id', $userId)
            ->first();

        if (! $areaCrop) {
            $this->throwAreaCropNotFound();
        }

        $areaCrop->toggleActive();

        return $areaCrop->fresh()->load('crop');
    }

    private function resolveActiveCrop(?string $uuid, ?int $id, bool $strict = true): ?Crop
    {
        $query = Crop::query()->where('is_active', true);

        if ($uuid !== null) {
            $query->where('uuid', $uuid);
        } elseif ($id !== null) {
            $query->where('id', $id);
        }

        $crop = $query->first();

        if ($strict && (! $crop)) {
            $this->throwInactiveCrop();
        }

        return $crop;
    }

    private function getAttachedCrop(Area $area, int $cropId): Crop
    {
        $attached = $area->crops()->where('crops.id', $cropId)->first();

        if (! $attached) {
            $this->throwAreaCropNotFound();
        }

        return $attached;
    }

    private function throwAreaCropNotFound(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_NOT_FOUND->value, 404);
    }

    private function throwCropNotFound(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_MISSING_CROP->value, 404);
    }

    private function throwInactiveCrop(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_INACTIVE_CROP->value, 404);
    }

    private function throwNoValidCrops(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_NONE_ATTACHED->value, 422);
    }

    public function validateAreaAllocation(Area $area, float $requestedArea, ?int $excludeCropId = null): void
    {
        if ($requestedArea <= 0) {
            $this->throwInvalidArea();
        }

        $totalAreaValue = (float) $area->area;

        if ($requestedArea > $totalAreaValue) {
            $this->throwAreaExceedsTotal();
        }

        $usedArea = (float) AreaCrop::query()
            ->where('area_id', $area->id)
            ->where('active', true)
            ->whereNull('harvested_at')
            ->when($excludeCropId !== null, fn ($query) => $query->where('id', '!=', $excludeCropId))
            ->sum('area');

        $availableArea = max(0.0, $totalAreaValue - $usedArea);

        if ($requestedArea > $availableArea) {
            $this->throwInsufficientSpace($availableArea, $requestedArea);
        }
    }

    public function harvestCrop(Area $area, string $cropUuid, ?float $yieldAmount, ?string $yieldUnit, ?string $notes): AreaCrop
    {
        $crop = Crop::where('uuid', $cropUuid)->first();

        if (! $crop) {
            $this->throwCropNotFound();
        }

        $areaCrop = AreaCrop::where('area_id', $area->id)
            ->where('crop_id', $crop->id)
            ->first();

        if (! $areaCrop) {
            $this->throwAreaCropNotFound();
        }

        if ($areaCrop->isHarvested()) {
            $this->throwAlreadyHarvested();
        }

        DB::transaction(static function () use ($areaCrop, $yieldAmount, $yieldUnit, $notes): void {
            $areaCrop->harvest($yieldAmount, $yieldUnit, $notes);
        });

        return $areaCrop->fresh()->load('crop');
    }

    private function throwInvalidArea(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_INVALID_AREA->value, 422);
    }

    private function throwAreaExceedsTotal(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_EXCEEDS_TOTAL->value, 422);
    }

    private function throwInsufficientSpace(float $available, float $requested): void
    {
        throw new CoreException(
            CoreTranslationKey::AREA_CROP_INSUFFICIENT_SPACE->value,
            422,
            [
                'available' => number_format($available / 10000, 2),
                'requested' => number_format($requested / 10000, 2),
            ]
        );
    }

    private function throwAlreadyHarvested(): void
    {
        throw new CoreException(CoreTranslationKey::AREA_CROP_ALREADY_HARVESTED->value, 422);
    }

    private function createUserCrop(int $userId, int $cropId, int $areaCropId): void
    {
        DB::table('user_crops')->insert([
            'user_id' => $userId,
            'crop_id' => $cropId,
            'area_crop_id' => $areaCropId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, int>  $areaCrops  Map of crop_id => area_crop_id
     */
    private function createUserCropsFromMap(int $userId, array $areaCrops): void
    {
        $records = [];

        foreach ($areaCrops as $cropId => $areaCropId) {
            $records[] = [
                'user_id' => $userId,
                'crop_id' => $cropId,
                'area_crop_id' => $areaCropId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records !== []) {
            DB::table('user_crops')->insert($records);
        }
    }
}
