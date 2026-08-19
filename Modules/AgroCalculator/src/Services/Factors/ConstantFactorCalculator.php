<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

final class ConstantFactorCalculator extends AbstractFactorCalculator
{
    public function supports(string $type): bool
    {
        return $type === 'constant';
    }

    public function calculate(array $factor, array $inputs, array $parameters): array
    {
        $score = (float) ($factor['config']['score'] ?? 1.0);

        return [
            'score' => $this->clip($score),
            'value' => null,
            'context' => 'constant',
        ];
    }
}
