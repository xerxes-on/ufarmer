<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\AgroCalculator\Data\ResolvedCalculationData;
use Modules\AgroCalculator\Models\CalculatorRun;
use Modules\AgroCalculator\Models\ScoringFlag;
use Modules\AgroCalculator\Models\ScoringModel;
use Modules\AgroCalculator\Models\ScoringRun;
use Modules\AgroCalculator\Models\ScoringThreshold;

final class ScoringEngine
{
    private const ERROR_KEY_PREFIX = 'agrocalculator::messages.errors';

    private const DEFAULT_WEIGHT = 1.0;

    private const FACTOR_FLAG_THRESHOLD = 0.75;

    public function run(CalculatorRun $calculatorRun, ResolvedCalculationData $resolved, array $calculatorResult): array
    {
        $scoringModel = $this->findActiveScoringModel();
        $weights = Arr::get($scoringModel->spec, 'agronomic.weights', []);
        $factorScores = Arr::map($calculatorResult['factors'], static fn (array $factor) => $factor['score']);
        $agronomicScore = $this->calculateWeightedAverage($factorScores, $weights);
        $riskScore = $this->calculateRiskScore($calculatorResult['stress_index']);
        $dataQualityScore = $this->calculateDataQualityScore($resolved);

        $metrics = [
            'agronomic_score' => $agronomicScore,
            'risk_score' => $riskScore,
            'data_quality_score' => $dataQualityScore,
        ];

        $grade = $this->determineGrade($scoringModel, $agronomicScore);
        $scoringRun = $this->persistScoringRun($calculatorRun, $scoringModel, $metrics, $grade);
        $flags = $this->createFlags($scoringRun, $calculatorResult['factors'], $scoringModel);

        return [
            'scoring_run' => $scoringRun->fresh(['flags']),
            'flags' => $flags,
            'metrics' => $metrics,
            'grade' => $grade,
        ];
    }

    private function findActiveScoringModel(): ScoringModel
    {
        $model = ScoringModel::query()
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if ($model === null) {
            throw ValidationException::withMessages([
                'scoring_model' => trans(self::ERROR_KEY_PREFIX.'.scoring_model_missing'),
            ]);
        }

        return $model;
    }

    private function calculateWeightedAverage(array $items, array $weights): float
    {
        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($items as $key => $value) {
            $weight = $weights[$key] ?? self::DEFAULT_WEIGHT;
            $weightedSum += $value * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0.0) {
            return 0.0;
        }

        return round(($weightedSum / $weightTotal) * 100, 2);
    }

    private function calculateRiskScore(float $stressIndex): float
    {
        $score = (1.0 - $stressIndex) * 100;

        return round(max(0.0, min(100.0, $score)), 2);
    }

    private function calculateDataQualityScore(ResolvedCalculationData $resolved): float
    {
        $inputs = $resolved->inputs;
        $required = Arr::get($resolved->parameters, 'inputs.required', []);
        $available = 0;
        $total = 0;
        foreach ($required as $category => $keys) {
            foreach ($keys as $key) {
                $total++;
                if (Arr::get($inputs, $category.'.'.$key) !== null) {
                    $available++;
                }
            }
        }

        if ($total === 0) {
            return 100.0;
        }

        return round(($available / $total) * 100, 2);
    }

    private function determineGrade(ScoringModel $model, float $score): ?string
    {
        /** @var Collection<int, ScoringThreshold> $thresholds */
        $thresholds = $model->thresholds
            ->where('metric_key', 'score')
            ->sortBy('min_value');

        foreach ($thresholds as $threshold) {
            $min = $threshold->min_value;
            $max = $threshold->max_value;

            $minPass = $min === null || $score >= (float) $min;
            $maxPass = $max === null || $score <= (float) $max;

            if ($minPass && $maxPass) {
                return $threshold->label;
            }
        }

        return null;
    }

    private function persistScoringRun(CalculatorRun $calculatorRun, ScoringModel $model, array $metrics, ?string $grade): ScoringRun
    {
        return ScoringRun::query()->create([
            'area_crop_id' => $calculatorRun->area_crop_id,
            'scoring_model_id' => $model->id,
            'calculator_run_id' => $calculatorRun->id,
            'inputs' => $calculatorRun->inputs,
            'outputs' => $metrics,
            'score' => $metrics['agronomic_score'] ?? null,
            'grade' => $grade,
            'engine_version' => $model->version,
            'hash' => null,
        ]);
    }

    private function createFlags(ScoringRun $scoringRun, array $factors, ScoringModel $model): \Illuminate\Support\Collection
    {
        $threshold = Arr::get($model->spec, 'flags.factor_threshold', self::FACTOR_FLAG_THRESHOLD);
        $flags = collect();

        foreach ($factors as $key => $factor) {
            if (($factor['score'] ?? 1.0) >= $threshold) {
                continue;
            }

            $flags->push(ScoringFlag::query()->create([
                'scoring_run_id' => $scoringRun->id,
                'code' => 'FACTOR_'.strtoupper((string) $key),
                'severity' => 'warning',
                'context' => [
                    'factor' => $key,
                    'score' => $factor['score'],
                ],
            ]));
        }

        return $flags;
    }
}
