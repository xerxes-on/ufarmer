<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Exceptions\CoreException;
use Modules\Core\Http\Requests\AreaCrop\AreaCropAttachMultipleRequest;
use Modules\Core\Http\Requests\AreaCrop\AreaCropDestroyRequest;
use Modules\Core\Http\Requests\AreaCrop\AreaCropIndexRequest;
use Modules\Core\Http\Requests\AreaCrop\AreaCropStoreRequest;
use Modules\Core\Http\Requests\AreaCrop\AreaCropToggleRequest;
use Modules\Core\Http\Requests\AreaCrop\AreaCropUpdateRequest;
use Modules\Core\Http\Requests\AreaCrop\HarvestAreaCropRequest;
use Modules\Core\Models\Area;
use Modules\Core\Services\AreaCropService;
use Modules\Core\Services\AreaService;

final class AreaCropController extends Controller
{
    public function __construct(
        private readonly AreaService $areaService,
        private readonly AreaCropService $areaCropService
    ) {}

    public function index(AreaCropIndexRequest $request, Area $area): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        $crops = $this->areaCropService->paginateAreaCrops(
            $area,
            $request->includeInactive(),
            $request->perPage()
        );

        return $this->respondSuccess($crops);
    }

    public function store(AreaCropStoreRequest $request, Area $area): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        try {
            $result = $this->areaCropService->attachCrop(
                $area,
                $request->cropUuid(),
                $request->cropId(),
                $request->area((float) $area->area),
                $request->dateStarted(),
                $request->expectedHarvestDate(),
                (int) $request->user()->id
            );
        } catch (CoreException $exception) {
            return $this->respondError(
                __($exception->translationKey, $exception->context ?? []),
                $exception->statusCode
            );
        }

        return $this->respondSuccess(
            $result['crop'],
            __(CoreTranslationKey::AREA_CROP_ATTACHED->value),
            201
        );
    }

    public function update(AreaCropUpdateRequest $request, Area $area, string $uuid): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        try {
            $updated = $this->areaCropService->updateAreaCrop(
                $area,
                $uuid,
                $request->area(),
                $request->dateStarted(),
                $request->expectedHarvestDate()
            );
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey, $exception->context ?? []), $exception->statusCode);
        }

        return $this->respondSuccess($updated, __(CoreTranslationKey::AREA_CROP_UPDATED->value));
    }

    public function destroy(AreaCropDestroyRequest $request, Area $area, string $uuid): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        try {
            $this->areaCropService->detachCrop($area, $uuid);
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey, $exception->context ?? []), $exception->statusCode);
        }

        return $this->respondSuccess(null, __(CoreTranslationKey::AREA_CROP_DETACHED->value));
    }

    public function attachMultiple(AreaCropAttachMultipleRequest $request, Area $area): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        try {
            $result = $this->areaCropService->attachMultiple(
                $area,
                $request->items(),
                (int) $request->user()->id
            );
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey, $exception->context ?? []), $exception->statusCode);
        }

        return $this->respondSuccess(
            $result['crops'],
            __(CoreTranslationKey::AREA_CROP_ATTACHED->value),
            201
        );
    }

    public function toggleActive(AreaCropToggleRequest $request, Area $area, string $uuid): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        try {
            $areaCrop = $this->areaCropService->toggleAreaCrop($area, $uuid, (int) $request->user()->id);
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey, $exception->context ?? []), $exception->statusCode);
        }

        $message = $areaCrop->active
            ? __(CoreTranslationKey::AREA_CROP_ACTIVATED->value)
            : __(CoreTranslationKey::AREA_CROP_DEACTIVATED->value);

        return $this->respondSuccess(
            [
                'active' => $areaCrop->active,
                'area_crop' => $areaCrop,
            ],
            $message
        );
    }

    public function harvest(HarvestAreaCropRequest $request, Area $area, string $uuid): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        try {
            $areaCrop = $this->areaCropService->harvestCrop(
                $area,
                $uuid,
                $request->yieldAmount(),
                $request->yieldUnit(),
                $request->notes()
            );
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey, $exception->context ?? []), $exception->statusCode);
        }

        return $this->respondSuccess($areaCrop, __(CoreTranslationKey::AREA_CROP_HARVESTED->value));
    }

    private function ensureOwnershipResponse(Area $area, int $userId): ?JsonResponse
    {
        try {
            $this->areaService->ensureOwnership($area, $userId);
        } catch (AuthorizationException) {
            return $this->respondError(__(CoreTranslationKey::AUTH_UNAUTHORIZED->value), 403);
        }

        return null;
    }
}
