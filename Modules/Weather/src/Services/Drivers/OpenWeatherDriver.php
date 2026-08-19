<?php

declare(strict_types=1);

namespace Modules\Weather\Services\Drivers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Weather\DTOs\DailyWeatherData;
use Modules\Weather\DTOs\HourlyWeatherData;
use Modules\Weather\DTOs\WeeklyWeatherData;
use Modules\Weather\Services\Contracts\WeatherDriverInterface;
use RuntimeException;

class OpenWeatherDriver implements WeatherDriverInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.openweathermap.org/data/2.5/weather'
    ) {}

    public function getHourlyWeather(float $latitude, float $longitude): HourlyWeatherData
    {
        $cacheKey = "weather:hourly:{$latitude}:{$longitude}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($latitude, $longitude) {
            $response = $this->makeCurrentWeatherRequest($latitude, $longitude);

            return $this->convertToHourlyData($response, $latitude, $longitude);
        });
    }

    public function getDailyWeather(float $latitude, float $longitude): DailyWeatherData
    {
        $cacheKey = "weather:daily:{$latitude}:{$longitude}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($latitude, $longitude) {
            $response = $this->makeCurrentWeatherRequest($latitude, $longitude);

            return $this->convertToDailyData($response, $latitude, $longitude);
        });
    }

    public function getWeeklyWeather(float $latitude, float $longitude): WeeklyWeatherData
    {
        $cacheKey = "weather:weekly:{$latitude}:{$longitude}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($latitude, $longitude) {
            $response = $this->makeCurrentWeatherRequest($latitude, $longitude);

            return $this->convertToWeeklyData($response, $latitude, $longitude);
        });
    }

    private function makeCurrentWeatherRequest(float $latitude, float $longitude): array
    {
        $response = Http::get($this->baseUrl, [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $this->apiKey,
            'units' => 'metric',
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Failed to fetch weather data: {$response->body()}");
        }

        return $response->json();
    }

    private function makeForecastRequest(float $latitude, float $longitude): array
    {
        $forecastUrl = str_replace('/weather', '/forecast', $this->baseUrl);

        $response = Http::get($forecastUrl, [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $this->apiKey,
            'units' => 'metric',
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Failed to fetch forecast data: {$response->body()}");
        }

        return $response->json();
    }

    private function convertToHourlyData(array $currentData, float $latitude, float $longitude): HourlyWeatherData
    {
        $forecastData = $this->makeForecastRequest($latitude, $longitude);

        $hours = array_slice(array_map(function ($item) {
            $temp = $item['main']['temp'];
            $humidity = $item['main']['humidity'];
            $dewPoint = $temp - ((100 - $humidity) / 5);

            return [
                'dt' => $item['dt'],
                'temp' => $temp,
                'feels_like' => $item['main']['feels_like'],
                'pressure' => $item['main']['pressure'],
                'humidity' => $humidity,
                'wind_speed' => $item['wind']['speed'] ?? 0,
                'wind_deg' => $item['wind']['deg'] ?? 0,
                'clouds' => $item['clouds']['all'] ?? 0,
                'uvi' => 0,
                'visibility' => $item['visibility'] ?? 10000,
                'weather' => $item['weather'] ?? [],
                'rain' => $item['rain']['3h'] ?? null,
                'snow' => $item['snow']['3h'] ?? null,
                'dew_point' => round($dewPoint, 2),
            ];
        }, $forecastData['list'] ?? []), 0, 8);

        return HourlyWeatherData::fromArray([
            'lat' => $latitude,
            'lon' => $longitude,
            'timezone' => 'UTC',
            'hourly' => $hours,
        ]);
    }

    private function convertToDailyData(array $data, float $latitude, float $longitude): DailyWeatherData
    {
        $temp = $data['main']['temp'];
        $humidity = $data['main']['humidity'];
        $dewPoint = $temp - ((100 - $humidity) / 5);

        $daily = [
            'dt' => $data['dt'],
            'sunrise' => $data['sys']['sunrise'] ?? 0,
            'sunset' => $data['sys']['sunset'] ?? 0,
            'moonrise' => 0,
            'moonset' => 0,
            'moon_phase' => 0,
            'temp' => [
                'day' => $temp,
                'min' => $data['main']['temp_min'],
                'max' => $data['main']['temp_max'],
                'night' => $temp,
                'eve' => $temp,
                'morn' => $temp,
            ],
            'feels_like' => [
                'day' => $data['main']['feels_like'],
                'night' => $data['main']['feels_like'],
                'eve' => $data['main']['feels_like'],
                'morn' => $data['main']['feels_like'],
            ],
            'pressure' => $data['main']['pressure'],
            'humidity' => $humidity,
            'wind_speed' => $data['wind']['speed'] ?? 0,
            'wind_deg' => $data['wind']['deg'] ?? 0,
            'clouds' => $data['clouds']['all'] ?? 0,
            'uvi' => 0,
            'weather' => $data['weather'] ?? [],
            'rain' => $data['rain']['1h'] ?? null,
            'snow' => $data['snow']['1h'] ?? null,
            'dew_point' => round($dewPoint, 2),
        ];

        return DailyWeatherData::fromArray([
            'lat' => $latitude,
            'lon' => $longitude,
            'timezone' => 'UTC',
            'daily' => [$daily],
        ]);
    }

    private function convertToWeeklyData(array $currentData, float $latitude, float $longitude): WeeklyWeatherData
    {
        $forecastData = $this->makeForecastRequest($latitude, $longitude);

        $dailyData = [];
        $currentDay = null;

        foreach ($forecastData['list'] ?? [] as $item) {
            $day = date('Y-m-d', $item['dt']);

            if ($day !== $currentDay) {
                $temp = $item['main']['temp'];
                $humidity = $item['main']['humidity'];
                $dewPoint = $temp - ((100 - $humidity) / 5);

                $dailyData[$day] = [
                    'dt' => $item['dt'],
                    'sunrise' => 0,
                    'sunset' => 0,
                    'moonrise' => 0,
                    'moonset' => 0,
                    'moon_phase' => 0,
                    'temp' => [
                        'day' => $temp,
                        'min' => $item['main']['temp_min'],
                        'max' => $item['main']['temp_max'],
                        'night' => $temp,
                        'eve' => $temp,
                        'morn' => $temp,
                    ],
                    'feels_like' => [
                        'day' => $item['main']['feels_like'],
                        'night' => $item['main']['feels_like'],
                        'eve' => $item['main']['feels_like'],
                        'morn' => $item['main']['feels_like'],
                    ],
                    'pressure' => $item['main']['pressure'],
                    'humidity' => $humidity,
                    'wind_speed' => $item['wind']['speed'] ?? 0,
                    'wind_deg' => $item['wind']['deg'] ?? 0,
                    'clouds' => $item['clouds']['all'] ?? 0,
                    'uvi' => 0,
                    'weather' => $item['weather'] ?? [],
                    'rain' => $item['rain']['3h'] ?? null,
                    'snow' => $item['snow']['3h'] ?? null,
                    'dew_point' => round($dewPoint, 2),
                ];
                $currentDay = $day;
            } else {
                $dailyData[$day]['temp']['min'] = min($dailyData[$day]['temp']['min'], $item['main']['temp_min']);
                $dailyData[$day]['temp']['max'] = max($dailyData[$day]['temp']['max'], $item['main']['temp_max']);
            }
        }

        return WeeklyWeatherData::fromArray([
            'lat' => $latitude,
            'lon' => $longitude,
            'timezone' => 'UTC',
            'daily' => array_values(array_slice($dailyData, 0, 7)),
        ]);
    }
}
