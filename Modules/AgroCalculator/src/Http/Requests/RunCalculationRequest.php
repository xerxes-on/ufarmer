<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RunCalculationRequest extends FormRequest
{
    public const FIELD_CROP_ID = 'crop_id';

    public const FIELD_PLANTING_ID = 'planting_id';

    public const FIELD_AREA_CROP_ID = 'area_crop_id';

    public const FIELD_PLANTING_DATE = 'planting_date';

    public const FIELD_SEASON = 'season';

    public const FIELD_CYCLE_DAYS = 'cycle_days';

    public const FIELD_SOIL = 'soil_inputs';

    public const FIELD_WATER = 'water_inputs';

    public const FIELD_CLIMATE = 'climate_inputs';

    public const FIELD_MANAGEMENT = 'management_inputs';

    public const FIELD_PARAMETER_OVERRIDES = 'parameter_overrides';

    public const FIELD_AREA_ID = 'area_id';

    public const FIELD_USER_ID = 'user_id';

    public const FIELD_ORGANIZATION_ID = 'organization_id';

    public const FIELD_INPUTS = 'inputs';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_CROP_ID => ['required', 'integer', 'exists:crops,id'],
            self::FIELD_AREA_CROP_ID => ['nullable', 'integer', 'exists:area_crops,id'],
            self::FIELD_PLANTING_ID => ['nullable', 'integer', 'exists:area_crops,id'],
            self::FIELD_PLANTING_DATE => ['nullable', 'date'],
            self::FIELD_SEASON => ['nullable', 'string', 'max:50'],
            self::FIELD_CYCLE_DAYS => ['nullable', 'integer', 'min:1'],
            self::FIELD_INPUTS => ['nullable', 'array'],
            self::FIELD_INPUTS.'.*' => ['array'],
            self::FIELD_SOIL => ['nullable', 'array'],
            self::FIELD_WATER => ['nullable', 'array'],
            self::FIELD_CLIMATE => ['nullable', 'array'],
            self::FIELD_MANAGEMENT => ['nullable', 'array'],
            self::FIELD_PARAMETER_OVERRIDES => ['nullable', 'array'],
            self::FIELD_PARAMETER_OVERRIDES.'.*' => ['nullable'],
            self::FIELD_AREA_ID => ['nullable', 'integer'],
            self::FIELD_USER_ID => ['nullable', 'integer'],
            self::FIELD_ORGANIZATION_ID => ['nullable', 'integer'],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        if (! isset($validated[self::FIELD_AREA_CROP_ID]) && isset($validated[self::FIELD_PLANTING_ID])) {
            $validated[self::FIELD_AREA_CROP_ID] = $validated[self::FIELD_PLANTING_ID];
        }

        if (! isset($validated[self::FIELD_AREA_CROP_ID])) {
            throw ValidationException::withMessages([
                self::FIELD_AREA_CROP_ID => trans('validation.required', ['attribute' => self::FIELD_AREA_CROP_ID]),
            ]);
        }

        return $validated;
    }
}
