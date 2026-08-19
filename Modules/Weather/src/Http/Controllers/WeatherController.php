<?php

declare(strict_types=1);

namespace Modules\Weather\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Weather\Http\Requests\CropAlertRequest;
use Modules\Weather\Http\Requests\CropForecastRequest;
use Modules\Weather\Http\Requests\WeatherRequest;
use Modules\Weather\Services\WeatherService;

final class WeatherController extends Controller
{
    public function __construct(
        private readonly WeatherService $weatherService
    ) {}

    public function hourly(WeatherRequest $request): JsonResponse
    {
        $data = $this->weatherService->getHourlyWeather(
            latitude: $request->getLatitude(),
            longitude: $request->getLongitude()
        );

        return $this->respondSuccess($data);
    }

    public function daily(WeatherRequest $request): JsonResponse
    {
        $data = $this->weatherService->getDailyWeather(
            latitude: $request->getLatitude(),
            longitude: $request->getLongitude()
        );

        return $this->respondSuccess($data);
    }

    public function weekly(WeatherRequest $request): JsonResponse
    {
        $data = $this->weatherService->getWeeklyWeather(
            latitude: $request->getLatitude(),
            longitude: $request->getLongitude()
        );

        return $this->respondSuccess($data);
    }

    public function cropAlert(CropAlertRequest $request): JsonResponse
    {
        $data = $this->weatherService->getCropAlert(
            cropId: $request->getCropId(),
            latitude: $request->getLatitude(),
            longitude: $request->getLongitude()
        );

        return $this->respondSuccess($data);
    }

    public function cropForecast(CropForecastRequest $request): JsonResponse
    {
        $data = $this->weatherService->getCropForecast(
            cropId: $request->getCropId(),
            latitude: $request->getLatitude(),
            longitude: $request->getLongitude()
        );

        return $this->respondSuccess($data);
    }
}
