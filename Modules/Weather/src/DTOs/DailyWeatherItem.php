<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

class DailyWeatherItem
{
    /**
     * @param  array<int, WeatherCondition>  $weather
     */
    public function __construct(
        public readonly int $timestamp,
        public readonly float $sunrise,
        public readonly float $sunset,
        public readonly float $moonrise,
        public readonly float $moonset,
        public readonly float $moonPhase,
        public readonly TemperatureData $temperature,
        public readonly FeelsLikeData $feelsLike,
        public readonly int $pressure,
        public readonly int $humidity,
        public readonly float $windSpeed,
        public readonly int $windDegree,
        public readonly int $clouds,
        public readonly float $uvi,
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
            sunrise: $data['sunrise'] ?? 0.0,
            sunset: $data['sunset'] ?? 0.0,
            moonrise: $data['moonrise'] ?? 0.0,
            moonset: $data['moonset'] ?? 0.0,
            moonPhase: $data['moon_phase'] ?? 0.0,
            temperature: TemperatureData::fromArray($data['temp'] ?? []),
            feelsLike: FeelsLikeData::fromArray($data['feels_like'] ?? []),
            pressure: $data['pressure'] ?? 0,
            humidity: $data['humidity'] ?? 0,
            windSpeed: $data['wind_speed'] ?? 0.0,
            windDegree: $data['wind_deg'] ?? 0,
            clouds: $data['clouds'] ?? 0,
            uvi: $data['uvi'] ?? 0.0,
            weather: $weather,
            rain: $data['rain'] ?? null,
            snow: $data['snow'] ?? null,
            dewPoint: $data['dew_point'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'sunrise' => $this->sunrise,
            'sunset' => $this->sunset,
            'moonrise' => $this->moonrise,
            'moonset' => $this->moonset,
            'moon_phase' => $this->moonPhase,
            'temperature' => $this->temperature->toArray(),
            'feels_like' => $this->feelsLike->toArray(),
            'pressure' => $this->pressure,
            'humidity' => $this->humidity,
            'wind_speed' => $this->windSpeed,
            'wind_degree' => $this->windDegree,
            'clouds' => $this->clouds,
            'uvi' => $this->uvi,
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
