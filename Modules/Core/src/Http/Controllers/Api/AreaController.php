<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Http\Requests\Area\AreaCalculateRequest;
use Modules\Core\Http\Requests\Area\AreaDestroyRequest;
use Modules\Core\Http\Requests\Area\AreaIndexRequest;
use Modules\Core\Http\Requests\Area\AreaShowRequest;
use Modules\Core\Http\Requests\Area\AreaStoreRequest;
use Modules\Core\Http\Requests\Area\AreaUpdateRequest;
use Modules\Core\Models\Area;
use Modules\Core\Services\AreaService;

final class AreaController extends Controller
{
    public function __construct(private readonly AreaService $areaService) {}

    public function index(AreaIndexRequest $request): JsonResponse
    {
        $areas = $this->areaService->paginateUserAreas($request->user(), $request->perPage());

        return $this->respondSuccess($areas);
    }

    public function store(AreaStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $area = $this->areaService->createArea(
            $user,
            $request->name(),
            $request->coordinates(),
            $request->areaValue()
        );

        return $this->respondSuccess(
            $area,
            __(CoreTranslationKey::AREA_CREATED->value),
            201
        );
    }

    public function show(AreaShowRequest $request, Area $area): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        $area->load('crops');
        $area->setAttribute('available_area', $area->getAvailableArea());

        return $this->respondSuccess($area);
    }

    public function update(AreaUpdateRequest $request, Area $area): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        $updated = $this->areaService->updateArea(
            $area,
            $request->name(),
            $request->coordinates()
        );

        return $this->respondSuccess(
            $updated,
            __(CoreTranslationKey::AREA_UPDATED->value)
        );
    }

    public function destroy(AreaDestroyRequest $request, Area $area): JsonResponse
    {
        if ($response = $this->ensureOwnershipResponse($area, (int) $request->user()->id)) {
            return $response;
        }

        $this->areaService->deleteArea($area);

        return $this->respondSuccess(null, __(CoreTranslationKey::AREA_DELETED->value));
    }

    public function calculateArea(AreaCalculateRequest $request): JsonResponse
    {
        $metrics = $this->areaService->calculateAreaMetrics($request->coordinates());

        return $this->respondSuccess($metrics);
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
