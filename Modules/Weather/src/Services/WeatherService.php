<?php

declare(strict_types=1);

namespace Modules\Weather\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Modules\Crops\Models\Crop;
use Modules\Weather\Contracts\WeatherServiceInterface;
use Modules\Weather\Services\Contracts\WeatherDriverInterface;

class WeatherService implements WeatherServiceInterface
{
    public function __construct(
        private readonly WeatherAlertService $alertService
    ) {}

    public function getHourlyWeather(float $latitude, float $longitude): array
    {
        $weather = $this->getWeatherDriver();
        $data = $weather->getHourlyWeather(
            latitude: $latitude,
            longitude: $longitude
        );

        return $data->toArray();
    }

    public function getDailyWeather(float $latitude, float $longitude): array
    {
        $weather = $this->getWeatherDriver();
        $data = $weather->getDailyWeather(
            latitude: $latitude,
            longitude: $longitude
        );

        return $data->toArray();
    }

    public function getWeeklyWeather(float $latitude, float $longitude): array
    {
        $weather = $this->getWeatherDriver();
        $data = $weather->getWeeklyWeather(
            latitude: $latitude,
            longitude: $longitude
        );

        return $data->toArray();
    }

    public function getCropAlert(int $cropId, float $latitude, float $longitude): array
    {
        $cacheKey = "weather:crop-alert:{$cropId}:{$latitude}:{$longitude}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($cropId, $latitude, $longitude) {
            $crop = Crop::findOrFail($cropId);

            $weather = $this->getWeatherDriver();
            $currentWeather = $weather->getDailyWeather(
                latitude: $latitude,
                longitude: $longitude
            );

            $currentTemp = $currentWeather->days[0]->temperature->day ?? 0.0;

            $alert = $this->alertService->analyzeTemperature($crop, $currentTemp);

            return [
                'crop' => [
                    'id' => $crop->id,
                    'name' => $crop->name,
                ],
                'alert' => $alert->toArray(),
            ];
        });
    }

    public function getCropForecast(string $cropId, float $latitude, float $longitude): array
    {
        $cacheKey = "weather:crop-forecast:{$cropId}:{$latitude}:{$longitude}";

        return Cache::remember($cacheKey, now()->addDays(5), function () use ($cropId, $latitude, $longitude) {
            $crop = Crop::where('id', $cropId)->firstOrFail();

            $weather = $this->getWeatherDriver();

            $currentWeather = $weather->getDailyWeather(latitude: $latitude, longitude: $longitude);
            $hourlyWeather = $weather->getHourlyWeather(latitude: $latitude, longitude: $longitude);
            $weeklyWeather = $weather->getWeeklyWeather(latitude: $latitude, longitude: $longitude);

            $todayTemp = $currentWeather->days[0]->temperature->day ?? 0.0;
            $todayAlert = $this->alertService->analyzeTemperature($crop, $todayTemp);

            $todayDate = date('Y-m-d');
            $hourlyData = array_filter($hourlyWeather->hours, function ($hour) use ($todayDate) {
                return date('Y-m-d', $hour->timestamp) === $todayDate;
            });

            $hourlyFormatted = array_map(function ($hour) use ($crop) {
                $temp = $hour->temperature;
                $alert = $this->alertService->analyzeTemperature($crop, $temp);

                return [
                    'time' => date('H:i', $hour->timestamp),
                    'timestamp' => $hour->timestamp,
                    'temperature' => $temp,
                    'feels_like' => $hour->feelsLike,
                    'humidity' => $hour->humidity,
                    'dew_point' => $hour->dewPoint,
                    'wind_speed' => $hour->windSpeed,
                    'weather' => $hour->weather[0] ?? null,
                    'alert' => [
                        'severity' => $alert->severity->value,
                        'color' => $alert->severity->getColor(),
                        'color_name' => $alert->severity->getColorName(),
                    ],
                ];
            }, array_values($hourlyData));

            $forecast = [];
            foreach (array_slice($weeklyWeather->days, 0, 5) as $day) {
                $dayTemp = $day->temperature->day;
                $alert = $this->alertService->analyzeTemperature($crop, $dayTemp);

                $forecast[] = [
                    'date' => date('Y-m-d', $day->timestamp),
                    'temperature' => [
                        'current' => $dayTemp,
                        'min' => $day->temperature->min,
                        'max' => $day->temperature->max,
                    ],
                    'weather' => $day->weather[0] ?? null,
                    'alert' => [
                        'severity' => $alert->severity->value,
                        'color' => $alert->severity->getColor(),
                        'color_name' => $alert->severity->getColorName(),
                        'title' => $alert->title,
                        'message' => $alert->message,
                    ],
                ];
            }

            $todayWeather = $currentWeather->days[0];

            return [
                'crop' => [
                    'uuid' => $crop->uuid,
                    'name' => $crop->name,
                    'recommended_temp_min' => $crop->recommended_temp_min,
                    'recommended_temp_max' => $crop->recommended_temp_max,
                ],
                'today' => [
                    'temperature' => $todayTemp,
                    'humidity' => $todayWeather->humidity ?? null,
                    'dew_point' => $todayWeather->dewPoint ?? null,
                    'wind_speed' => $todayWeather->windSpeed ?? null,
                    'weather' => $todayWeather->weather[0] ?? null,
                    'alert' => [
                        'severity' => $todayAlert->severity->value,
                        'color' => $todayAlert->severity->getColor(),
                        'color_name' => $todayAlert->severity->getColorName(),
                        'title' => $todayAlert->title,
                        'message' => $todayAlert->message,
                        'temperature_difference' => $todayAlert->temperatureDifference,
                    ],
                    'hourly' => $hourlyFormatted,
                ],
                'forecast' => $forecast,
            ];
        });
    }

    private function getWeatherDriver(): WeatherDriverInterface
    {
        return App::make('weather')->driver();
    }
}
