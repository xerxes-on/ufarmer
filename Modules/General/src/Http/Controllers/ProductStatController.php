<?php

declare(strict_types=1);

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\General\Exceptions\GeneralException;
use Modules\General\Http\Requests\ProductStat\CropStatsRequest;
use Modules\General\Http\Requests\ProductStat\ProductStatTodayRequest;
use Modules\General\Http\Resources\ProductStatResource;
use Modules\General\Services\CropPriceHistoryService;
use Modules\General\Services\ProductStatService;
use Xerxes\AuthBridge\Models\Farmer;

final class ProductStatController extends Controller
{
    public function __construct(private readonly ProductStatService $productStatService) {}

    public function today(ProductStatTodayRequest $request): JsonResponse
    {
        try {
            $stat = $this->productStatService->getTodayStat();
        } catch (GeneralException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess(ProductStatResource::make($stat));
    }

    public function getCropStats(
        CropStatsRequest $request,
        CropPriceHistoryService $service
    ): JsonResponse {
        /** @var Farmer $farmer */
        $farmer = $request->user();

        try {
            $stats = $this->productStatService->getCropHistory($farmer, $request->cropId(), $service);
        } catch (GeneralException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess($stats);
    }
}
