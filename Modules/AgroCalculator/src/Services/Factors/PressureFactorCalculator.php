<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Support\Arr;

final class PressureFactorCalculator extends AbstractFactorCalculator
{
    /** @var array<string, mixed> */
    private array $pressureDefaults = [];

    /**
     * @param  array<string, mixed>  $pressureDefaults
     */
    public function setPressureDefaults(array $pressureDefaults): void
    {
        $this->pressureDefaults = $pressureDefaults;
    }

    public function supports(string $type): bool
    {
        return $type === 'pressure';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $config = $factor['config'] ?? [];
        $reportedInput = $config['reported_input'] ?? null;
        $reported = null;
        if (is_array($reportedInput)) {
            $category = $reportedInput['category'] ?? null;
            $key = $reportedInput['key'] ?? null;
            if ($category && $key) {
                $value = Arr::get($inputs, $category.'.'.$key);
                $reported = $value !== null ? (float) $value : null;
            }
        }

        if ($reported !== null) {
            return [
                'score' => $this->scoreFromPressure($reported),
                'value' => $reported,
                'context' => 'reported',
            ];
        }

        $climateConfig = $config['climate'] ?? [];
        $humidity = $this->resolveInputValue([
            'input' => $climateConfig['humidity_input'] ?? ['category' => 'climate', 'key' => 'rh'],
        ], $inputs, $parameters);
        $temperature = $this->resolveInputValue([
            'input' => $climateConfig['temperature_input'] ?? ['category' => 'climate', 'key' => 'avg_temp'],
        ], $inputs, $parameters);

        $score = 1.0;
        $humidityBands = $climateConfig['humidity_bands'] ?? [];
        foreach ($humidityBands as $band => $value) {
            if ($humidity >= (float) $band) {
                $score = min($score, (float) $value);
            }
        }

        $trigger = $climateConfig['humidity_trigger'] ?? null;
        $temperatureThreshold = $climateConfig['temperature_threshold'] ?? null;
        $fallback = $climateConfig['fallback'] ?? null;
        if ($trigger !== null && $temperatureThreshold !== null && $fallback !== null) {
            if ($humidity >= (float) $trigger && $temperature >= (float) $temperatureThreshold) {
                $score = min($score, (float) $fallback);
            }
        }

        return [
            'score' => $this->clip($score),
            'value' => $humidity,
            'context' => 'climate',
        ];
    }

    private function scoreFromPressure(float $pressure): float
    {
        $optimal = (float) ($this->pressureDefaults['score_optimal'] ?? 1.0);
        $lowScore = (float) ($this->pressureDefaults['score_low'] ?? 0.9);
        $mediumScore = (float) ($this->pressureDefaults['score_medium'] ?? 0.7);
        $highScore = (float) ($this->pressureDefaults['score_high'] ?? 0.5);
        $lowThreshold = (float) ($this->pressureDefaults['threshold_low'] ?? 0.1);
        $mediumThreshold = (float) ($this->pressureDefaults['threshold_medium'] ?? 0.3);

        if ($pressure <= 0.0) {
            return $optimal;
        }

        if ($pressure < $lowThreshold) {
            return $lowScore;
        }

        if ($pressure < $mediumThreshold) {
            return $mediumScore;
        }

        return $highScore;
    }
}
