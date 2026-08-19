<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

class TemperatureData
{
    public function __construct(
        public readonly float $day,
        public readonly float $min,
        public readonly float $max,
        public readonly float $night,
        public readonly float $evening,
        public readonly float $morning,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            day: $data['day'] ?? 0.0,
            min: $data['min'] ?? 0.0,
            max: $data['max'] ?? 0.0,
            night: $data['night'] ?? 0.0,
            evening: $data['eve'] ?? 0.0,
            morning: $data['morn'] ?? 0.0,
        );
    }

    public function toArray(): array
    {
        return [
            'day' => $this->day,
            'min' => $this->min,
            'max' => $this->max,
            'night' => $this->night,
            'evening' => $this->evening,
            'morning' => $this->morning,
        ];
    }
}
