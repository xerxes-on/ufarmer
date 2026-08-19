<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services\Factors;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\AgroCalculator\Contracts\FactorCalculatorInterface;

abstract class AbstractFactorCalculator implements FactorCalculatorInterface
{
    protected const ERROR_KEY_PREFIX = 'agrocalculator::messages.errors';

    protected function clip(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $parameters
     */
    protected function resolveInputValue(array $definition, array $inputs, array $parameters): float
    {
        $inputDefinition = $definition['input'] ?? [];
        $category = $inputDefinition['category'] ?? null;
        $key = $inputDefinition['key'] ?? null;

        if ($category === null || $key === null) {
            throw ValidationException::withMessages([
                'parameters.inputs' => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
            ]);
        }

        $sources = Arr::get($parameters, 'inputs.sources', []);
        $categories = [];
        if ($category !== null) {
            $categories[] = $category;
        }
        if (is_array($sources)) {
            $categories = array_merge($categories, array_keys($sources));
        }
        $categories = array_merge($categories, array_keys($inputs));
        $requiredCategories = Arr::get($parameters, 'inputs.required', []);
        if (is_array($requiredCategories)) {
            $categories = array_merge($categories, array_keys($requiredCategories));
        }
        $categories = array_values(array_unique(array_filter($categories)));

        foreach ($categories as $candidate) {
            $value = Arr::get($inputs, $candidate.'.'.$key);
            if ($value !== null && $value !== '') {
                return (float) $value;
            }

            $fallback = Arr::get($parameters, $candidate.'.'.$key);
            if ($fallback !== null && $fallback !== '') {
                return (float) $fallback;
            }
        }

        throw ValidationException::withMessages([
            'inputs.'.$category.'.'.$key => trans(self::ERROR_KEY_PREFIX.'.input_missing', ['input' => $category.'.'.$key]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string>  $requiredKeys
     */
    protected function validateConfig(array $config, array $requiredKeys, string $factorType): void
    {
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $config) || $config[$key] === null) {
                throw ValidationException::withMessages([
                    'parameters.factors.'.$factorType => trans(self::ERROR_KEY_PREFIX.'.calculation_failed'),
                ]);
            }
        }
    }
}
