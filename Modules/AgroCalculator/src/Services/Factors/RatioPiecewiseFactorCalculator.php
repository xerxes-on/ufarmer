<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Validation\ValidationException;

final class RatioPiecewiseFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'ratio_piecewise';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $input = $this->resolveInputValue($factor, $inputs, $parameters);
        $config = $factor['config'] ?? [];
        $optimal = $config['optimal'] ?? null;
        $bands = $config['bands'] ?? [];
        $fallback = $config['fallback'] ?? null;

        if ($optimal === null || $optimal <= 0.0 || $fallback === null) {
            throw ValidationException::withMessages([
                'parameters.factors.ratio_piecewise' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        $ratio = $input / (float) $optimal;
        foreach ($bands as $band) {
            $min = $band['min'] ?? null;
            $max = $band['max'] ?? null;
            $value = $band['value'] ?? $band['val'] ?? null;

            if ($min === null || $max === null || $value === null) {
                continue;
            }

            if ($ratio >= (float) $min && $ratio <= (float) $max) {
                return [
                    'score' => $this->clip((float) $value),
                    'value' => $input,
                    'ratio' => $ratio,
                    'target' => (float) $optimal,
                ];
            }
        }

        return [
            'score' => $this->clip((float) $fallback),
            'value' => $input,
            'ratio' => $ratio,
            'target' => (float) $optimal,
        ];
    }
}
