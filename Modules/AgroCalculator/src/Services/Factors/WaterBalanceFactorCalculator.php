<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Validation\ValidationException;

final class WaterBalanceFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'water_balance';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $input = $this->resolveInputValue($factor, $inputs, $parameters);
        $config = $factor['config'] ?? [];
        $optimal = $config['optimal'] ?? null;
        $ky = $config['ky'] ?? null;

        if ($optimal === null || $optimal <= 0.0 || $ky === null) {
            throw ValidationException::withMessages([
                'parameters.factors.water_balance' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        if ($input >= (float) $optimal) {
            $score = 1.0;
        } else {
            $ratio = $input / (float) $optimal;
            $score = max(0.0, 1.0 - (float) $ky * (1.0 - $ratio));
        }

        return [
            'score' => $this->clip($score),
            'value' => $input,
            'target' => (float) $optimal,
        ];
    }
}
