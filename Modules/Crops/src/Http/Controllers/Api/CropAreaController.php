<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Http\Requests\Crop\CropAreaAttachRequest;
use Modules\Crops\Services\CropAreaService;

final class CropAreaController extends Controller
{
    public function __construct(private readonly CropAreaService $cropAreaService) {}

    public function attachMultiple(CropAreaAttachRequest $request, string $id): JsonResponse
    {
        try {
            $areas = $this->cropAreaService->attachMultiple($request->user(), $id, $request->items());
        } catch (CropsException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess(
            $areas,
            __(CropsTranslationKey::CROP_AREA_CREATED->value),
            201
        );
    }
}
