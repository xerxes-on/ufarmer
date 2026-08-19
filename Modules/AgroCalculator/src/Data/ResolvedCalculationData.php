<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Data;

use Modules\AgroCalculator\Models\CropParameterSet;
use Modules\Core\Models\AreaCrop;

final readonly class ResolvedCalculationData
{
    public function __construct(
        public AreaCrop $areaCrop,
        public CropParameterSet $parameterSet,
        public array $parameters,
        public array $inputs,
        public array $overrides
    ) {}
}
