<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

class WeatherCondition
{
    public function __construct(
        public readonly string $main,
        public readonly string $description,
        public readonly string $icon,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            main: $data['main'] ?? '',
            description: $data['description'] ?? '',
            icon: $data['icon'] ?? '',
        );
    }
}
