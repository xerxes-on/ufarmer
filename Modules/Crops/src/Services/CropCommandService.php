<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Models\Crop;

final class CropCommandService
{
    public function attachCrop(User $user, string $cropId, ?int $areaCropId = null): Crop
    {
        $crop = Crop::where('id', $cropId)
            ->active()
            ->first();

        if (! $crop) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 404);
        }

        if ($areaCropId === null) {
            $hasNullAreaCrop = DB::table('user_crops')
                ->where('user_id', $user->id)
                ->where('crop_id', $crop->id)
                ->whereNull('area_crop_id')
                ->exists();

            if ($hasNullAreaCrop) {
                throw new CropsException(CropsTranslationKey::USER_CROP_ALREADY_EXISTS->value, 422);
            }
        }

        DB::table('user_crops')->insert([
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'area_crop_id' => $areaCropId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $crop;
    }

    public function detachCrop(User $user, string $cropId): void
    {
        $crop = Crop::where('id', $cropId)->first();

        if (! $crop) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 404);
        }

        $deleted = DB::table('user_crops')
            ->where('user_id', $user->id)
            ->where('crop_id', $crop->id)
            ->whereNull('area_crop_id')
            ->delete();

        if ($deleted === 0) {
            throw new CropsException(CropsTranslationKey::USER_CROP_NOT_FOUND->value, 404);
        }
    }

    public function syncCrops(User $user, array $cropIds): EloquentCollection
    {
        $validCropIds = Crop::whereIn('id', $cropIds)
            ->active()
            ->pluck('id')
            ->all();

        DB::transaction(static function () use ($user, $validCropIds): void {
            $user->crops()->sync($validCropIds);
        });

        $crops = $user->crops()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name->en')
            ->get();

        $crops->transform(function (Crop $crop) {
            if ($crop->in_agrocalendar) {
                $crop->setAttribute('calendar_details', $crop->calendar_details);
            }

            return $crop;
        });

        return $crops;
    }

    public function attachMultiple(User $user, array $cropIds): EloquentCollection
    {
        $validCropIds = Crop::whereIn('id', $cropIds)
            ->active()
            ->pluck('id')
            ->all();

        $existing = $user->crops()->pluck('crops.id')->all();
        $toAttach = array_values(array_diff($validCropIds, $existing));

        if ($toAttach === []) {
            return new EloquentCollection;
        }

        DB::transaction(static function () use ($user, $toAttach): void {
            $user->crops()->attach($toAttach);
        });

        $added = Crop::whereIn('id', $toAttach)->get();

        $added->transform(function (Crop $crop) {
            if ($crop->in_agrocalendar) {
                $crop->setAttribute('calendar_details', $crop->calendar_details);
            }

            return $crop;
        });

        return $added;
    }
}
