<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Validation\ValidationException;

final class TemperatureTriangleFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'temperature_triangle';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $input = $this->resolveInputValue($factor, $inputs, $parameters);
        $config = $factor['config'] ?? [];
        $min = $config['min'] ?? null;
        $max = $config['max'] ?? null;
        $center = $config['center'] ?? null;

        if ($min === null || $max === null || $center === null || $min >= $max) {
            throw ValidationException::withMessages([
                'parameters.factors.temperature' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        if ($input >= (float) $min && $input <= (float) $max) {
            $score = 1.0;
        } else {
            $divider = (float) $max - (float) $min;
            $score = max(0.0, 1.0 - abs($input - (float) $center) / $divider);
        }

        return [
            'score' => $this->clip($score),
            'value' => $input,
            'range' => ['min' => (float) $min, 'max' => (float) $max, 'center' => (float) $center],
        ];
    }
}
