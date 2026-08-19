<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Models\Crop;

final class CropAreaService
{
    public function attachMultiple(User $user, string $cropId, array $items)
    {
        $crop = Crop::where('id', $cropId)->active()->first();

        if (! $crop) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 404);
        }

        $areaIds = collect($items)->pluck('area_id')->unique()->values()->all();

        $ownedAreaIds = $user->areas()->whereIn('areas.id', $areaIds)->pluck('areas.id')->all();
        if (count($ownedAreaIds) !== count($areaIds)) {
            throw new CropsException(CropsTranslationKey::CROP_AREA_UNAUTHORIZED->value, 403);
        }

        DB::transaction(static function () use ($crop, $areaIds): void {
            $crop->areas()->syncWithoutDetaching($areaIds);
        });

        return $crop->areas()
            ->whereIn('areas.id', $areaIds)
            ->orderBy('areas.id')
            ->get();
    }
}
