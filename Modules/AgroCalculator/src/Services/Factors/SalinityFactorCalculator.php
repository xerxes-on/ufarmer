<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Validation\ValidationException;

final class SalinityFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'salinity';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $input = $this->resolveInputValue($factor, $inputs, $parameters);
        $config = $factor['config'] ?? [];
        $threshold = $config['threshold'] ?? null;
        $slope = $config['slope'] ?? null;

        if ($threshold === null || $slope === null || $slope < 0.0) {
            throw ValidationException::withMessages([
                'parameters.factors.salinity' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        $score = $input <= (float) $threshold
            ? 1.0
            : max(0.0, 1.0 - (float) $slope * ($input - (float) $threshold));

        return [
            'score' => $this->clip($score),
            'value' => $input,
            'threshold' => (float) $threshold,
        ];
    }
}
