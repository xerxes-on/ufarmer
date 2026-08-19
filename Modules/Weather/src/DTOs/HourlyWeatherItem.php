<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

class HourlyWeatherItem
{
    /**
     * @param  array<int, WeatherCondition>  $weather
     */
    public function __construct(
        public readonly int $timestamp,
        public readonly float $temperature,
        public readonly float $feelsLike,
        public readonly int $pressure,
        public readonly int $humidity,
        public readonly float $windSpeed,
        public readonly int $windDegree,
        public readonly int $clouds,
        public readonly float $uvi,
        public readonly int $visibility,
        public readonly array $weather,
        public readonly ?float $rain = null,
        public readonly ?float $snow = null,
        public readonly ?float $dewPoint = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $weather = array_map(
            fn (array $w) => WeatherCondition::fromArray($w),
            $data['weather'] ?? []
        );

        return new self(
            timestamp: $data['dt'] ?? 0,
            temperature: $data['temp'] ?? 0.0,
            feelsLike: $data['feels_like'] ?? 0.0,
            pressure: $data['pressure'] ?? 0,
            humidity: $data['humidity'] ?? 0,
            windSpeed: $data['wind_speed'] ?? 0.0,
            windDegree: $data['wind_deg'] ?? 0,
            clouds: $data['clouds'] ?? 0,
            uvi: $data['uvi'] ?? 0.0,
            visibility: $data['visibility'] ?? 0,
            weather: $weather,
            rain: $data['rain']['1h'] ?? null,
            snow: $data['snow']['1h'] ?? null,
            dewPoint: $data['dew_point'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'temperature' => $this->temperature,
            'feels_like' => $this->feelsLike,
            'pressure' => $this->pressure,
            'humidity' => $this->humidity,
            'wind_speed' => $this->windSpeed,
            'wind_degree' => $this->windDegree,
            'clouds' => $this->clouds,
            'uvi' => $this->uvi,
            'visibility' => $this->visibility,
            'weather' => array_map(fn (WeatherCondition $w) => [
                'main' => $w->main,
                'description' => $w->description,
                'icon' => $w->icon,
            ], $this->weather),
            'rain' => $this->rain,
            'snow' => $this->snow,
            'dew_point' => $this->dewPoint,
        ];
    }
}
