<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

class HourlyWeatherData
{
    /**
     * @param  array<int, HourlyWeatherItem>  $hours
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $timezone,
        public readonly array $hours,
    ) {}

    public static function fromArray(array $data): self
    {
        $hours = array_map(
            fn (array $hour) => HourlyWeatherItem::fromArray($hour),
            array_slice($data['hourly'] ?? [], 0, 24)
        );

        return new self(
            latitude: $data['lat'] ?? 0.0,
            longitude: $data['lon'] ?? 0.0,
            timezone: $data['timezone'] ?? '',
            hours: $hours,
        );
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'hours' => array_map(fn (HourlyWeatherItem $item) => $item->toArray(), $this->hours),
        ];
    }
}
