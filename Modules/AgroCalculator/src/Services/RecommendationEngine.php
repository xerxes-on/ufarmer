<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services;

use Illuminate\Support\Collection;
use Modules\AgroCalculator\Data\ResolvedCalculationData;
use Modules\AgroCalculator\Models\RecommendationRule;
use Modules\AgroCalculator\Support\ConditionEvaluator;

final readonly class RecommendationEngine
{
    public function __construct(private ConditionEvaluator $conditionEvaluator) {}

    public function generate(ResolvedCalculationData $resolved, array $calculatorResult, array $metrics, ?string $grade): Collection
    {
        $parametersContext = array_merge(
            $resolved->parameters,
            [
                'metrics' => $metrics,
                'factors' => $calculatorResult['factors'] ?? [],
                'risk_level' => $calculatorResult['risk_level'] ?? null,
                'grade' => $grade,
            ],
        );

        return RecommendationRule::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (RecommendationRule $rule) use ($parametersContext, $resolved): bool {
                return $this->conditionEvaluator->matches($rule->conditions, $parametersContext, $resolved->inputs);
            })
            ->map(static function (RecommendationRule $rule): array {
                return [
                    'code' => $rule->code,
                    'title' => $rule->getTranslations('title'),
                    'recommendations' => $rule->recommendations,
                ];
            });
    }
}
