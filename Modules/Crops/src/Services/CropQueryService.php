<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Models\Crop;

final class CropQueryService
{
    private const CACHE_TTL = 3600;

    public function paginateCrops(?string $search, int $perPage, int $page): array
    {
        $locale = app()->getLocale();
        $version = Cache::get('crops_version', 1);
        $searchKey = $search ? md5(mb_strtolower($search)) : 'none';
        $cacheKey = sprintf(
            'crops:index:v%s:page:%d:per:%d:search:%s:loc:%s',
            $version,
            $page,
            $perPage,
            $searchKey,
            $locale
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($search, $perPage) {
            $paginator = Crop::query()
                ->active()
                ->when($search, function ($query, $value): void {
                    $query->where(function ($q) use ($value): void {
                        $lower = mb_strtolower($value);
                        $q->whereRaw('LOWER(name::text) LIKE ?', ['%'.$lower.'%']);
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('name->en')
                ->paginate($perPage);

            $paginator->getCollection()->transform(function (Crop $crop) {
                if ($crop->in_agrocalendar) {
                    $crop->setAttribute('calendar_details', $crop->calendar_details);
                }

                return $crop;
            });

            return $paginator->toArray();
        });
    }

    public function getCropById(string $id): Crop
    {
        $crop = Crop::where('id', $id)
            ->active()
            ->first();

        if (! $crop) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 404);
        }

        if ($crop->in_agrocalendar) {
            $crop->setAttribute('calendar_details', $crop->calendar_details);
        }

        return $crop;
    }

    public function paginateUserCrops(User $user, int $perPage): LengthAwarePaginator
    {
        $paginator = $user->crops()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name->en')
            ->paginate($perPage);

        $collection = $paginator->getCollection();

        $collection->load(['areas' => function ($query) use ($user): void {
            $query->where('areas.user_id', $user->id);
        }]);

        $collection->transform(function (Crop $crop) {
            if ($crop->in_agrocalendar) {
                $crop->setAttribute('calendar_details', $crop->calendar_details);
            }

            $userAreas = $crop->areas->map(function (Area $area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'area' => $area->pivot ? (float) $area->pivot->area : null,
                    'date_started' => $area->pivot?->date_started,
                ];
            })->values();

            $crop->setAttribute('user_areas', $userAreas);

            return $crop;
        });

        return $paginator;
    }

    public function getActiveCrop(string $cropId): Crop
    {
        $crop = Crop::where('id', $cropId)
            ->active()
            ->first();

        if (! $crop) {
            throw new CropsException(CropsTranslationKey::CROP_NOT_FOUND->value, 404);
        }

        return $crop;
    }
}
