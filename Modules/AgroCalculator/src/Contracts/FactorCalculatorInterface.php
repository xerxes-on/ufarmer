<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Contracts;

interface FactorCalculatorInterface
{
    public function supports(string $type): bool;

    /**
     * @param  array<string, mixed>  $factor
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $parameters
     * @return array{score: float, value: float|null, ...}
     */
    public function calculate(array $factor, array $inputs, array $parameters): array;
}
