<?php

declare(strict_types=1);

namespace Modules\Weather\Services\Contracts;

use Modules\Weather\DTOs\DailyWeatherData;
use Modules\Weather\DTOs\HourlyWeatherData;
use Modules\Weather\DTOs\WeeklyWeatherData;

interface WeatherDriverInterface
{
    public function getHourlyWeather(float $latitude, float $longitude): HourlyWeatherData;

    public function getDailyWeather(float $latitude, float $longitude): DailyWeatherData;

    public function getWeeklyWeather(float $latitude, float $longitude): WeeklyWeatherData;
}
