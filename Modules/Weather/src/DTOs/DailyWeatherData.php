<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

class DailyWeatherData
{
    /**
     * @param  array<int, DailyWeatherItem>  $days
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $timezone,
        public readonly array $days,
    ) {}

    public static function fromArray(array $data): self
    {
        $days = array_map(
            fn (array $day) => DailyWeatherItem::fromArray($day),
            array_slice($data['daily'] ?? [], 0, 1)
        );

        return new self(
            latitude: $data['lat'] ?? 0.0,
            longitude: $data['lon'] ?? 0.0,
            timezone: $data['timezone'] ?? '',
            days: $days,
        );
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'days' => array_map(fn (DailyWeatherItem $item) => $item->toArray(), $this->days),
        ];
    }
}
