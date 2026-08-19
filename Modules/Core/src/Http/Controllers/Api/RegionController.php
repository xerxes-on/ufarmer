<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Http\Requests\Region\RegionCitiesRequest;
use Modules\Core\Http\Requests\Region\RegionIndexRequest;
use Modules\Core\Http\Requests\Region\RegionShowRequest;
use Modules\Core\Models\Region;
use Modules\Core\Services\RegionService;

final class RegionController extends Controller
{
    public function __construct(private readonly RegionService $regionService) {}

    public function index(RegionIndexRequest $request): JsonResponse
    {
        $regions = $this->regionService->listRegions($request->includeCities());

        return $this->respondSuccess($regions);
    }

    public function show(RegionShowRequest $request, Region $region): JsonResponse
    {
        try {
            $region = $this->regionService->getRegion($region);
        } catch (ModelNotFoundException) {
            return $this->respondError(__(CoreTranslationKey::REGION_NOT_FOUND->value), 404);
        }

        return $this->respondSuccess($region);
    }

    public function cities(RegionCitiesRequest $request, Region $region): JsonResponse
    {
        try {
            $cities = $this->regionService->listRegionCities($region);
        } catch (ModelNotFoundException) {
            return $this->respondError(__(CoreTranslationKey::REGION_NOT_FOUND->value), 404);
        }

        return $this->respondSuccess($cities);
    }
}
