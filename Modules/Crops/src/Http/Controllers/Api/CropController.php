<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Crops\Enums\CropsTranslationKey;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Http\Requests\Crop\AttachCropRequest;
use Modules\Crops\Http\Requests\Crop\AttachMultipleCropsRequest;
use Modules\Crops\Http\Requests\Crop\CropIndexRequest;
use Modules\Crops\Http\Requests\Crop\DetachCropRequest;
use Modules\Crops\Http\Requests\Crop\SyncCropsRequest;
use Modules\Crops\Http\Requests\Crop\UserCropIndexRequest;
use Modules\Crops\Services\CropService;

final class CropController extends Controller
{
    public function __construct(private readonly CropService $cropService) {}

    public function index(CropIndexRequest $request): JsonResponse
    {
        $data = $this->cropService->paginateCrops(
            $request->search(),
            $request->perPage(),
            $request->page()
        );

        return $this->respondSuccess($data);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $crop = $this->cropService->getCropById($id);
        } catch (CropsException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess($crop);
    }

    public function userCrops(UserCropIndexRequest $request): JsonResponse
    {
        $paginator = $this->cropService->paginateUserCrops($request->user(), $request->perPage());
        $prices = $this->cropService->fetchExternalPrices($paginator);

        if ($prices !== []) {
            $paginator->getCollection()->transform(function ($crop) use ($prices) {
                $ruName = $crop->getTranslation('name', 'ru');

                if ($ruName && array_key_exists($ruName, $prices)) {
                    $entry = $prices[$ruName];
                    $price = is_array($entry) ? ($entry['price'] ?? null) : (is_scalar($entry) ? $entry : null);
                    $crop->setAttribute('external_price_ru', $price);
                }

                return $crop;
            });
        }

        return $this->respondSuccess([
            'data' => $paginator->toArray(),
            'external_prices_ru' => $prices,
        ]);
    }

    public function attachCrop(AttachCropRequest $request): JsonResponse
    {
        try {
            $crop = $this->cropService->attachCrop($request->user(), $request->cropId());
        } catch (CropsException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess($crop, __(CropsTranslationKey::CROP_ATTACHED->value));
    }

    public function detachCrop(DetachCropRequest $request, string $id): JsonResponse
    {
        try {
            $this->cropService->detachCrop($request->user(), $id);
        } catch (CropsException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess(null, __(CropsTranslationKey::CROP_DETACHED->value));
    }

    public function syncCrops(SyncCropsRequest $request): JsonResponse
    {
        $crops = $this->cropService->syncCrops($request->user(), $request->cropIds());

        return $this->respondSuccess($crops, __(CropsTranslationKey::USER_CROP_SYNCED->value));
    }

    public function attachMultipleCrops(AttachMultipleCropsRequest $request): JsonResponse
    {
        $added = $this->cropService->attachMultiple($request->user(), $request->cropIds());

        return $this->respondSuccess($added, __(CropsTranslationKey::USER_CROP_CREATED->value));
    }

    public function resetImages(): JsonResponse
    {
        $this->cropService->resetImages();

        return $this->respondSuccess(null, __(CropsTranslationKey::GENERAL_SUCCESS->value));
    }
}
