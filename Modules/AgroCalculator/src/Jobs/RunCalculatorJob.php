<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AgroCalculator\Data\CalculationResultData;
use Modules\AgroCalculator\Data\ResolvedCalculationData;
use Modules\AgroCalculator\Models\CalculatorRun;
use Modules\AgroCalculator\Models\CropParameterSet;
use Modules\AgroCalculator\Services\CalculatorEngine;
use Modules\AgroCalculator\Services\RecommendationEngine;
use Modules\AgroCalculator\Services\ScoringEngine;
use Modules\Core\Models\AreaCrop;
use Throwable;

final class RunCalculatorJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $areaCropId,
        private readonly int $parameterSetId,
        private readonly array $parameters,
        private readonly array $inputs,
        private readonly array $overrides
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        CalculatorEngine $calculator,
        ScoringEngine $scoring,
        RecommendationEngine $recommendations
    ): CalculationResultData {
        $areaCrop = AreaCrop::query()->findOrFail($this->areaCropId);
        $parameterSet = CropParameterSet::query()->findOrFail($this->parameterSetId);

        $resolved = new ResolvedCalculationData(
            areaCrop: $areaCrop,
            parameterSet: $parameterSet,
            parameters: $this->parameters,
            inputs: $this->inputs,
            overrides: $this->overrides,
        );

        $calculatorResult = $calculator->run($resolved);

        $calculatorRun = CalculatorRun::query()->create([
            'area_crop_id' => $areaCrop->id,
            'inputs' => [
                'parameters' => $this->parameters,
                'inputs' => $this->inputs,
                'overrides' => $this->overrides,
            ],
            'outputs' => $calculatorResult,
            'potential_yield_t_ha' => $calculatorResult['potential_yield_t_ha'] ?? null,
            'risk_level' => $calculatorResult['risk_level'] ?? null,
            'engine_version' => $parameterSet->version,
            'hash' => null,
        ]);

        $scoringResult = $scoring->run($calculatorRun, $resolved, $calculatorResult);
        $recommendationCollection = $recommendations->generate(
            $resolved,
            $calculatorResult,
            $scoringResult['metrics'],
            $scoringResult['grade']
        );

        return new CalculationResultData(
            calculatorRun: $calculatorRun->fresh('areaCrop'),
            scoringRun: $scoringResult['scoring_run'],
            recommendations: $recommendationCollection->values()->all()
        );
    }
}
