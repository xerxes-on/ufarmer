<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use InvalidArgumentException;
use Modules\AgroCalculator\Contracts\FactorCalculatorInterface;

final class FactorCalculatorFactory
{
    /** @var array<FactorCalculatorInterface> */
    private array $calculators;

    /**
     * @param  array<FactorCalculatorInterface>  $calculators
     */
    public function __construct(array $calculators)
    {
        $this->calculators = $calculators;
    }

    public function make(string $type): FactorCalculatorInterface
    {
        foreach ($this->calculators as $calculator) {
            if ($calculator->supports($type)) {
                return $calculator;
            }
        }

        throw new InvalidArgumentException("Unknown factor type: {$type}");
    }

    public function getPressureCalculator(): PressureFactorCalculator
    {
        foreach ($this->calculators as $calculator) {
            if ($calculator instanceof PressureFactorCalculator) {
                return $calculator;
            }
        }

        throw new InvalidArgumentException('PressureFactorCalculator not registered');
    }
}
