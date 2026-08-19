<?php

declare(strict_types=1);

namespace Modules\Weather\Services;

use Modules\Crops\Models\Crop;
use Modules\Weather\DTOs\WeatherAlertDTO;
use Modules\Weather\Enums\AlertSeverity;

class WeatherAlertService
{
    private const STATUS_EXTREMELY_COLD = 'extremely_cold';

    private const STATUS_VERY_COLD = 'very_cold';

    private const STATUS_COLD = 'cold';

    private const STATUS_EXTREMELY_HOT = 'extremely_hot';

    private const STATUS_VERY_HOT = 'very_hot';

    private const STATUS_HOT = 'hot';

    private const STATUS_OPTIMAL = 'optimal';

    private const STATUS_NO_RECOMMENDATION = 'no_recommendation';

    public function analyzeTemperature(Crop $crop, float $currentTemperature): WeatherAlertDTO
    {
        if ($crop->recommended_temp_min === null && $crop->recommended_temp_max === null) {
            return $this->createAlert(
                status: self::STATUS_NO_RECOMMENDATION,
                severity: AlertSeverity::OK,
                currentTemperature: $currentTemperature,
                recommendedTempMin: null,
                recommendedTempMax: null,
                temperatureDifference: null
            );
        }

        $minTemp = $crop->recommended_temp_min ?? -999;
        $maxTemp = $crop->recommended_temp_max ?? 999;

        if ($currentTemperature >= $minTemp && $currentTemperature <= $maxTemp) {
            return $this->createAlert(
                status: self::STATUS_OPTIMAL,
                severity: AlertSeverity::OK,
                currentTemperature: $currentTemperature,
                recommendedTempMin: $crop->recommended_temp_min,
                recommendedTempMax: $crop->recommended_temp_max,
                temperatureDifference: 0
            );
        }

        if ($currentTemperature < $minTemp) {
            $diff = round(abs($currentTemperature - $minTemp), 1);

            [$status, $severity] = match (true) {
                $diff >= 10 => [self::STATUS_EXTREMELY_COLD, AlertSeverity::CRITICAL],
                $diff >= 5 => [self::STATUS_VERY_COLD, AlertSeverity::WARNING],
                default => [self::STATUS_COLD, AlertSeverity::INFO],
            };

            return $this->createAlert(
                status: $status,
                severity: $severity,
                currentTemperature: $currentTemperature,
                recommendedTempMin: $crop->recommended_temp_min,
                recommendedTempMax: $crop->recommended_temp_max,
                temperatureDifference: -$diff
            );
        }

        $diff = round(abs($currentTemperature - $maxTemp), 1);

        [$status, $severity] = match (true) {
            $diff >= 10 => [self::STATUS_EXTREMELY_HOT, AlertSeverity::CRITICAL],
            $diff >= 5 => [self::STATUS_VERY_HOT, AlertSeverity::WARNING],
            default => [self::STATUS_HOT, AlertSeverity::INFO],
        };

        return $this->createAlert(
            status: $status,
            severity: $severity,
            currentTemperature: $currentTemperature,
            recommendedTempMin: $crop->recommended_temp_min,
            recommendedTempMax: $crop->recommended_temp_max,
            temperatureDifference: $diff
        );
    }

    private function createAlert(
        string $status,
        AlertSeverity $severity,
        float $currentTemperature,
        ?float $recommendedTempMin,
        ?float $recommendedTempMax,
        ?float $temperatureDifference
    ): WeatherAlertDTO {
        $languages = ['en', 'ru', 'uz'];
        $title = [];
        $message = [];

        foreach ($languages as $lang) {
            $translation = trans("weather::alerts.{$status}", [
                'diff' => abs($temperatureDifference ?? 0),
            ], $lang);

            $title[$lang] = is_array($translation) ? $translation['title'] : trans("weather::alerts.{$status}.title", [], $lang);
            $message[$lang] = is_array($translation) ? $translation['message'] : trans("weather::alerts.{$status}.message", ['diff' => abs($temperatureDifference ?? 0)], $lang);
        }

        return new WeatherAlertDTO(
            status: $status,
            severity: $severity,
            title: $title,
            message: $message,
            currentTemperature: $currentTemperature,
            recommendedTempMin: $recommendedTempMin,
            recommendedTempMax: $recommendedTempMax,
            temperatureDifference: $temperatureDifference
        );
    }
}
