<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Data;

use Modules\AgroCalculator\Models\CalculatorRun;
use Modules\AgroCalculator\Models\ScoringRun;

final readonly class CalculationResultData
{
    public function __construct(
        public CalculatorRun $calculatorRun,
        public ScoringRun $scoringRun,
        public array $recommendations
    ) {}
}
