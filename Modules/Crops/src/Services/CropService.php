<?php

declare(strict_types=1);

namespace Modules\Crops\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Crops\Models\Crop;

final class CropService
{
    public function __construct(
        private readonly CropQueryService $queryService,
        private readonly CropCommandService $commandService,
        private readonly CropPriceService $priceService
    ) {}

    public function paginateCrops(?string $search, int $perPage, int $page): array
    {
        return $this->queryService->paginateCrops($search, $perPage, $page);
    }

    public function getCropById(string $id): Crop
    {
        return $this->queryService->getCropById($id);
    }

    public function paginateUserCrops(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->queryService->paginateUserCrops($user, $perPage);
    }

    public function attachCrop(User $user, string $cropId, ?int $areaCropId = null): Crop
    {
        return $this->commandService->attachCrop($user, $cropId, $areaCropId);
    }

    public function detachCrop(User $user, string $cropId): void
    {
        $this->commandService->detachCrop($user, $cropId);
    }

    public function syncCrops(User $user, array $cropIds): EloquentCollection
    {
        return $this->commandService->syncCrops($user, $cropIds);
    }

    public function attachMultiple(User $user, array $cropIds): EloquentCollection
    {
        return $this->commandService->attachMultiple($user, $cropIds);
    }

    public function fetchExternalPrices(LengthAwarePaginator $paginator): array
    {
        return $this->priceService->fetchExternalPrices($paginator);
    }

    public function resetImages(): void
    {
        DB::transaction(function (): void {
            DB::table('user_crops')->truncate();
            Crop::query()->truncate();
            Artisan::call('db:seed --force --class=CropsSeeder');
        });

        Cache::increment('crops_version');
    }
}
