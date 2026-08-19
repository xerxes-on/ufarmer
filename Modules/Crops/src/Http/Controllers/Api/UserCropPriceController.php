<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Crops\Exceptions\CropsException;
use Modules\Crops\Http\Requests\UserCropPrice\UserCropPriceHistoryRequest;
use Modules\Crops\Http\Requests\UserCropPrice\UserCropPriceIndexRequest;
use Modules\Crops\Services\UserCropPriceService;

final class UserCropPriceController extends Controller
{
    public function __construct(private readonly UserCropPriceService $priceService) {}

    public function userCropsWithWeeklyPrices(UserCropPriceIndexRequest $request): JsonResponse
    {
        $paginator = $this->priceService->paginateUserCropsWithPrices($request->user(), $request->perPage());

        return $this->respondSuccess([
            'data' => $paginator->toArray(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function userCropPriceHistory(UserCropPriceHistoryRequest $request, string $cropId): JsonResponse
    {
        try {
            $history = $this->priceService->userCropPriceHistory(
                $request->user(),
                $cropId,
                $request->page(),
                $request->perPage()
            );
        } catch (CropsException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess($history);
    }
}
