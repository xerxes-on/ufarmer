<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Modules\AgroPrices\Http\Requests\GetTodayPricesRequest;
use Modules\AgroPrices\Http\Resources\DailyPriceResource;
use Modules\AgroPrices\Services\PriceService;

final class PriceController extends Controller
{
    public function __construct(
        private readonly PriceService $priceService,
    ) {}

    public function today(GetTodayPricesRequest $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $lang = $request->getLang();
        $prices = $this->priceService->getTodayPrices();

        $items = $prices->map(
            static fn ($price) => (new DailyPriceResource($price, $lang, $today))->resolve($request)
        );

        return $this->respondSuccess([
            'date' => $today,
            'count' => $items->count(),
            'items' => $items->values()->all(),
        ]);
    }
}
