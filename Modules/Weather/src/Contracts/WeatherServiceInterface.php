<?php

declare(strict_types=1);

namespace Modules\Weather\Contracts;

interface WeatherServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getHourlyWeather(float $latitude, float $longitude): array;

    /**
     * @return array<string, mixed>
     */
    public function getDailyWeather(float $latitude, float $longitude): array;

    /**
     * @return array<string, mixed>
     */
    public function getWeeklyWeather(float $latitude, float $longitude): array;

    /**
     * @return array{crop: array<string, mixed>, alert: array<string, mixed>}
     */
    public function getCropAlert(int $cropId, float $latitude, float $longitude): array;

    /**
     * @return array{crop: array<string, mixed>, today: array<string, mixed>, forecast: array<int, array<string, mixed>>}
     */
    public function getCropForecast(string $cropId, float $latitude, float $longitude): array;
}
