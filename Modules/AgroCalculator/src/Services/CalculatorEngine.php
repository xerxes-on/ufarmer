<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\AgroCalculator\Data\ResolvedCalculationData;
use Modules\AgroCalculator\Services\Factors\FactorCalculatorFactory;

final class CalculatorEngine
{
    private const ERROR_KEY_PREFIX = 'agrocalculator::messages.errors';

    public function __construct(
        private readonly FactorCalculatorFactory $factorCalculatorFactory
    ) {}

    public function run(ResolvedCalculationData $resolved): array
    {
        $parameters = $resolved->parameters;
        $inputs = $resolved->inputs;

        $baselineYield = $parameters['baseline_yield'] ?? null;
        if ($baselineYield === null) {
            throw ValidationException::withMessages([
                'parameters.baseline_yield' => trans(self::ERROR_KEY_PREFIX.'.parameter_not_defined', ['parameter' => 'baseline_yield']),
            ]);
        }

        $factorDefinitions = $parameters['factors'] ?? [];
        if ($factorDefinitions === []) {
            throw ValidationException::withMessages([
                'parameters.factors' => trans(self::ERROR_KEY_PREFIX.'.parameter_not_defined', ['parameter' => 'factors']),
            ]);
        }

        $defaults = $this->resolveDefaults($parameters);
        $defaultWeight = (float) $this->resolveDefaultValue($defaults, 'weights.default', 'defaults.weights.default');
        $riskDefaultLabel = (string) $this->resolveDefaultValue($defaults, 'risk.default_label', 'defaults.risk.default_label');
        $pressureDefaults = $this->resolvePressureDefaults($defaults);

        $this->factorCalculatorFactory->getPressureCalculator()->setPressureDefaults($pressureDefaults);

        $factors = [];
        foreach ($factorDefinitions as $definition) {
            $name = $definition['name'] ?? null;
            if ($name === null) {
                throw ValidationException::withMessages([
                    'parameters.factors' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
                ]);
            }

            $factors[$name] = $this->evaluateFactor($name, $definition, $parameters, $inputs);
        }

        $weights = $parameters['weights'] ?? [];
        $yield = $this->calculateYield((float) $baselineYield, $factors, $weights, $defaultWeight);

        $stressFactors = $parameters['risk']['stress_factors'] ?? array_keys($factors);
        $stress = $this->calculateStressIndex($factors, $stressFactors);
        $riskBands = $parameters['risk']['bands'] ?? [];
        $riskLevel = $this->determineRiskLevel($stress, $riskBands, $riskDefaultLabel);

        return [
            'potential_yield_t_ha' => $yield,
            'stress_index' => $stress,
            'risk_level' => $riskLevel,
            'factors' => $factors,
        ];
    }

    private function calculateYield(float $baseline, array $factors, array $weights, float $defaultWeight): float
    {
        $product = 1.0;

        foreach ($factors as $key => $factor) {
            $weight = $weights[$key] ?? $defaultWeight;
            $product *= pow(max($factor['score'], 0.0), $weight);
        }

        return round($baseline * $product, 2);
    }

    private function calculateStressIndex(array $factors, array $stressFactorNames): float
    {
        $stress = 1.0;

        foreach ($stressFactorNames as $key) {
            if (! array_key_exists($key, $factors)) {
                continue;
            }

            $stress *= (1.0 - $this->clip($factors[$key]['score']));
        }

        return round($this->clip($stress), 4);
    }

    private function determineRiskLevel(float $stress, array $bands, string $defaultLabel): string
    {
        foreach ($bands as $band) {
            $max = $band['max'] ?? null;
            $label = $band['label'] ?? null;

            if ($max === null || $label === null) {
                continue;
            }

            if ($stress <= (float) $max) {
                return (string) $label;
            }
        }

        return $defaultLabel;
    }

    private function clip(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    private function evaluateFactor(string $name, array $definition, array $parameters, array $inputs): array
    {
        $type = $definition['type'] ?? null;
        if ($type === null) {
            throw ValidationException::withMessages([
                'parameters.factors.'.$name => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        try {
            $calculator = $this->factorCalculatorFactory->make($type);

            return $calculator->calculate($definition, $inputs, $parameters);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'parameters.factors.'.$name => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }
    }

    private function resolveDefaults(array $parameters): array
    {
        if (! array_key_exists('defaults', $parameters) || ! is_array($parameters['defaults'])) {
            throw ValidationException::withMessages([
                'parameters.defaults' => trans(self::ERROR_KEY_PREFIX.'.parameter_not_defined', ['parameter' => 'defaults']),
            ]);
        }

        return $parameters['defaults'];
    }

    private function resolveDefaultValue(array $defaults, string $path, string $label): mixed
    {
        if (! Arr::has($defaults, $path)) {
            throw ValidationException::withMessages([
                'parameters.'.$label => trans(self::ERROR_KEY_PREFIX.'.parameter_not_defined', ['parameter' => $label]),
            ]);
        }

        return Arr::get($defaults, $path);
    }

    private function resolvePressureDefaults(array $defaults): array
    {
        $pressure = $this->resolveDefaultValue($defaults, 'pressure', 'defaults.pressure');
        if (! is_array($pressure)) {
            throw ValidationException::withMessages([
                'parameters.defaults.pressure' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        $requiredKeys = [
            'threshold_low',
            'threshold_medium',
            'score_optimal',
            'score_low',
            'score_medium',
            'score_high',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $pressure)) {
                throw ValidationException::withMessages([
                    'parameters.defaults.pressure.'.$key => trans(self::ERROR_KEY_PREFIX.'.parameter_not_defined', ['parameter' => 'defaults.pressure.'.$key]),
                ]);
            }
        }

        return $pressure;
    }
}
