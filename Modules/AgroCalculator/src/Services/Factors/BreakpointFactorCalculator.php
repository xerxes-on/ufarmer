<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

final class BreakpointFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'breakpoints';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $input = $this->resolveInputValue($factor, $inputs, $parameters);
        $config = $factor['config'] ?? [];
        $breakpoints = $config['breakpoints'] ?? [];
        $fallback = $config['fallback'] ?? 0.4;

        foreach ($breakpoints as $breakpoint) {
            $max = $breakpoint['max'] ?? null;
            $value = $breakpoint['value'] ?? $breakpoint['val'] ?? null;

            if ($max === null || $value === null) {
                continue;
            }

            if ($input <= (float) $max) {
                return [
                    'score' => $this->clip((float) $value),
                    'value' => $input,
                    'threshold' => (float) $max,
                ];
            }
        }

        return [
            'score' => $this->clip((float) $fallback),
            'value' => $input,
            'threshold' => null,
        ];
    }
}
