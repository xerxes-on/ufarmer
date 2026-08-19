<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AgroCalculator\Data\CalculationResultData;

class CalculationResultResource extends JsonResource
{
    /** @var CalculationResultData */
    public $resource;

    public function toArray($request): array
    {
        $calculatorRun = $this->resource->calculatorRun;
        $scoringRun = $this->resource->scoringRun;

        return [
            'calculator_run' => [
                'id' => $calculatorRun->id,
                'area_crop_id' => $calculatorRun->area_crop_id,
                'potential_yield_t_ha' => $calculatorRun->potential_yield_t_ha,
                'risk_level' => $calculatorRun->risk_level,
                'stress_index' => $calculatorRun->outputs['stress_index'] ?? null,
                'factors' => $calculatorRun->outputs['factors'] ?? [],
                'created_at' => $calculatorRun->created_at?->toISOString(),
            ],
            'scoring' => [
                'id' => $scoringRun->id,
                'score' => $scoringRun->score,
                'grade' => $scoringRun->grade,
                'metrics' => $scoringRun->outputs,
                'flags' => $scoringRun->flags->map(static function ($flag) {
                    return [
                        'code' => $flag->code,
                        'severity' => $flag->severity,
                        'context' => $flag->context,
                    ];
                })->all(),
                'created_at' => $scoringRun->created_at?->toISOString(),
            ],
            'recommendations' => $this->resource->recommendations,
        ];
    }
}
