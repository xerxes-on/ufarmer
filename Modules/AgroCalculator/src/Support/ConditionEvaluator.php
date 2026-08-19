<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Support;

use Illuminate\Support\Arr;

final class ConditionEvaluator
{
    private const array OPERATORS = ['<', '<=', '>', '>=', '==', '!='];

    public function matches(array $condition, array $parameters, array $inputs): bool
    {
        foreach ($condition as $key => $comparisons) {
            $value = $this->resolveValue($key, $parameters, $inputs);

            if ($value === null) {
                return false;
            }

            if (! is_array($comparisons)) {
                if ($value !== $comparisons) {
                    return false;
                }

                continue;
            }

            foreach ($comparisons as $operator => $expected) {
                if (! in_array($operator, self::OPERATORS, true)) {
                    return false;
                }

                if (! $this->compare($value, $expected, $operator)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function resolveValue(string $key, array $parameters, array $inputs): mixed
    {
        if (str_contains($key, '.')) {
            $inputValue = Arr::get($inputs, $key);
            if ($inputValue !== null) {
                return $inputValue;
            }

            return Arr::get($parameters, $key);
        }

        if (array_key_exists($key, $inputs)) {
            return $inputs[$key];
        }

        if (array_key_exists($key, $parameters)) {
            return $parameters[$key];
        }

        return Arr::get($inputs, 'soil.'.$key)
            ?? Arr::get($inputs, 'water.'.$key)
            ?? Arr::get($inputs, 'climate.'.$key)
            ?? Arr::get($parameters, 'soil.'.$key)
            ?? Arr::get($parameters, 'water.'.$key)
            ?? Arr::get($parameters, 'climate.'.$key);
    }

    private function compare(mixed $value, mixed $expected, string $operator): bool
    {
        return match ($operator) {
            '<' => $value < $expected,
            '<=' => $value <= $expected,
            '>' => $value > $expected,
            '>=' => $value >= $expected,
            '==' => $value === $expected,
            '!=' => $value !== $expected,
            default => false,
        };
    }
}
