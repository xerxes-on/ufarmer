<?php

declare(strict_types=1);

namespace Modules\Weather\DTOs;

use Modules\Weather\Enums\AlertSeverity;

class WeatherAlertDTO
{
    public function __construct(
        public readonly string $status,
        public readonly AlertSeverity $severity,
        public readonly array $title,
        public readonly array $message,
        public readonly float $currentTemperature,
        public readonly ?float $recommendedTempMin,
        public readonly ?float $recommendedTempMax,
        public readonly ?float $temperatureDifference,
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'severity' => $this->severity->value,
            'color' => $this->severity->getColor(),
            'color_name' => $this->severity->getColorName(),
            'title' => $this->title,
            'message' => $this->message,
            'current_temperature' => $this->currentTemperature,
            'recommended_temp_min' => $this->recommendedTempMin,
            'recommended_temp_max' => $this->recommendedTempMax,
            'temperature_difference' => $this->temperatureDifference,
        ];
    }
}
