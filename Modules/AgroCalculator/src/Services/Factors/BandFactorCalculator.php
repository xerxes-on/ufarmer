<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Validation\ValidationException;

final class BandFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'band';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $input = $this->resolveInputValue($factor, $inputs, $parameters);
        $config = $factor['config'] ?? [];
        $min = $config['min'] ?? null;
        $max = $config['max'] ?? null;

        if ($min === null || $max === null || $min >= $max) {
            throw ValidationException::withMessages([
                'parameters.factors.band' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        if ($input >= (float) $min && $input <= (float) $max) {
            $score = 1.0;
        } elseif ($input < (float) $min) {
            $score = max(0.0, $input / (float) $min);
        } else {
            $score = max(0.0, (float) $max / $input);
        }

        return [
            'score' => $this->clip($score),
            'value' => $input,
            'range' => ['min' => (float) $min, 'max' => (float) $max],
        ];
    }
}
