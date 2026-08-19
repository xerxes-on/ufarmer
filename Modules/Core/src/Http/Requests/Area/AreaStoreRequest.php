<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class AreaStoreRequest extends FormRequest
{
    public const FIELD_NAME = 'name';

    public const FIELD_COORDINATES = 'coordinates';

    public const FIELD_AREA = 'area';

    public const COORDINATE_LATITUDE = '0';

    public const COORDINATE_LONGITUDE = '1';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_NAME => ['required', 'string', 'max:255'],
            self::FIELD_COORDINATES => ['required', 'array', 'min:3'],
            self::FIELD_COORDINATES.'.*' => ['required', 'array', 'size:2'],
            self::FIELD_COORDINATES.'.*.'.self::COORDINATE_LATITUDE => ['required', 'numeric', 'between:-90,90'],
            self::FIELD_COORDINATES.'.*.'.self::COORDINATE_LONGITUDE => ['required', 'numeric', 'between:-180,180'],
            self::FIELD_AREA => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function name(): string
    {
        return $this->string(self::FIELD_NAME)->toString();
    }

    public function coordinates(): array
    {
        return $this->input(self::FIELD_COORDINATES, []);
    }

    public function areaValue(?float $fallback = null): ?float
    {
        return $this->filled(self::FIELD_AREA)
            ? (float) $this->input(self::FIELD_AREA)
            : $fallback;
    }
}
