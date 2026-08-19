<?php

declare(strict_types=1);

namespace Modules\Weather\Services;

use Illuminate\Support\Manager;
use Modules\Weather\Services\Drivers\OpenWeatherDriver;

class WeatherManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('weather.default', 'openweather');
    }

    protected function createOpenweatherDriver(): OpenWeatherDriver
    {
        return new OpenWeatherDriver(
            $this->config->get('weather.drivers.openweather.api_key'),
            $this->config->get('weather.drivers.openweather.base_url', 'https://api.openweathermap.org/data/2.5/onecall')
        );
    }
}
