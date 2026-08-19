<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\AgroCalculator\Data\ResolvedCalculationData;
use Modules\AgroCalculator\Http\Requests\RunCalculationRequest;
use Modules\AgroCalculator\Models\CropParameterSet;
use Modules\AgroCalculator\Models\PlantingParameterOverride;
use Modules\AgroCalculator\Support\ConditionEvaluator;
use Modules\Core\Models\AreaCrop;

final class ParameterResolver
{
    private const ERROR_KEY_PREFIX = 'agrocalculator::messages.errors';

    public function __construct(private readonly ConditionEvaluator $conditionEvaluator) {}

    /**
     * @throws ValidationException
     */
    public function resolve(int $cropId, array $payload, ?AreaCrop $areaCrop = null): ResolvedCalculationData
    {
        $parameterSet = $this->findActiveParameterSet($cropId);

        $parameters = $parameterSet->params;

        $overrides = $this->collectOverrides($areaCrop, $payload);
        foreach ($overrides as $override) {
            $parameters = array_replace_recursive($parameters, $override);
        }

        $inputs = $this->collectInputs($parameters, $areaCrop, $payload);
        $this->assertRequiredInputs($parameters, $inputs);

        $parameters = $this->applyDependencies($parameters, $inputs);

        $resolvedAreaCrop = $this->resolveAreaCrop($areaCrop, $payload, $cropId);

        return new ResolvedCalculationData(
            areaCrop: $resolvedAreaCrop,
            parameterSet: $parameterSet,
            parameters: $parameters,
            inputs: $inputs,
            overrides: $overrides,
        );
    }

    /**
     * @throws ValidationException
     */
    public function fetchParameterSet(int $cropId): CropParameterSet
    {
        return $this->findActiveParameterSet($cropId);
    }

    private function findActiveParameterSet(int $cropId): CropParameterSet
    {
        $now = Carbon::now()->toDateString();

        $parameterSet = CropParameterSet::query()
            ->where('crop_id', $cropId)
            ->where('is_active', true)
            ->where(static function ($query) use ($now): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(static function ($query) use ($now): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', $now);
            })
            ->orderByDesc('version')
            ->first();

        if ($parameterSet === null) {
            throw ValidationException::withMessages([
                'parameters' => trans(self::ERROR_KEY_PREFIX.'.parameters_missing'),
            ]);
        }

        return $parameterSet;
    }

    private function collectOverrides(?AreaCrop $areaCrop, array $payload): array
    {
        $overrides = [];

        if ($areaCrop !== null) {
            PlantingParameterOverride::query()
                ->where('area_crop_id', $areaCrop->id)
                ->orderBy('created_at')
                ->get()
                ->each(static function (PlantingParameterOverride $override) use (&$overrides): void {
                    $overrides[] = $override->params;
                });
        }

        if (array_key_exists('parameter_overrides', $payload)) {
            $overrides[] = $payload['parameter_overrides'];
        }

        return $overrides;
    }

    private function collectInputs(array $parameters, ?AreaCrop $areaCrop, array $payload): array
    {
        $rawInputs = $payload['inputs'] ?? [];
        $inputs = is_array($rawInputs) ? $rawInputs : [];
        $sources = Arr::get($parameters, 'inputs.sources');
        if (! is_array($sources) || $sources === []) {
            throw ValidationException::withMessages([
                'parameters.inputs.sources' => trans(self::ERROR_KEY_PREFIX.'.parameter_not_defined', ['parameter' => 'inputs.sources']),
            ]);
        }

        foreach ($sources as $category => $field) {
            if (array_key_exists($field, $payload)) {
                $inputs[$category] = $payload[$field];

                continue;
            }
            if ($areaCrop !== null && $areaCrop->getAttribute($field) !== null) {
                $inputs[$category] = $areaCrop->getAttribute($field);
            }
        }

        return $inputs;
    }

    private function assertRequiredInputs(array $parameters, array $inputs): void
    {
        $requirements = Arr::get($parameters, 'inputs.required', []);

        foreach ($requirements as $category => $keys) {
            if (! array_key_exists($category, $inputs)) {
                throw ValidationException::withMessages([
                    'inputs.'.$category => trans(self::ERROR_KEY_PREFIX.'.input_missing', ['input' => $category]),
                ]);
            }

            foreach ($keys as $key) {
                $value = Arr::get($inputs[$category], $key);
                if ($value === null || $value === '') {
                    throw ValidationException::withMessages([
                        'inputs.'.$category.'.'.$key => trans(self::ERROR_KEY_PREFIX.'.input_missing', ['input' => $category.'.'.$key]),
                    ]);
                }
            }
        }
    }

    private function applyDependencies(array $parameters, array $inputs): array
    {
        $dependencies = $parameters['dependencies'] ?? [];
        if ($dependencies === []) {
            return $parameters;
        }

        foreach ($dependencies as $rule) {
            $condition = $rule['if'] ?? [];
            if ($condition === []) {
                continue;
            }

            if (! $this->conditionEvaluator->matches($condition, $parameters, $inputs)) {
                continue;
            }

            $update = $rule['then'] ?? [];
            if (! is_array($update)) {
                continue;
            }

            $parameters = array_replace_recursive($parameters, $update);
        }

        return $parameters;
    }

    private function resolveAreaCrop(?AreaCrop $areaCrop, array $payload, int $cropId): AreaCrop
    {
        if ($areaCrop !== null) {
            return $areaCrop;
        }

        $areaCropId = $payload[RunCalculationRequest::FIELD_AREA_CROP_ID] ?? null;
        if ($areaCropId !== null) {
            return AreaCrop::query()->findOrFail((int) $areaCropId);
        }

        throw ValidationException::withMessages([
            RunCalculationRequest::FIELD_AREA_CROP_ID => trans('validation.required', ['attribute' => RunCalculationRequest::FIELD_AREA_CROP_ID]),
        ]);
    }
}
