<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class AreaUpdateRequest extends FormRequest
{
    public const FIELD_NAME = AreaStoreRequest::FIELD_NAME;

    public const FIELD_COORDINATES = AreaStoreRequest::FIELD_COORDINATES;

    public const FIELD_AREA = AreaStoreRequest::FIELD_AREA;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_NAME => ['sometimes', 'required', 'string', 'max:255'],
            self::FIELD_COORDINATES => ['sometimes', 'required', 'array', 'min:3'],
            self::FIELD_COORDINATES.'.*' => ['required_with:'.self::FIELD_COORDINATES, 'array', 'size:2'],
            self::FIELD_COORDINATES.'.*.'.AreaStoreRequest::COORDINATE_LATITUDE => ['required_with:'.self::FIELD_COORDINATES, 'numeric', 'between:-90,90'],
            self::FIELD_COORDINATES.'.*.'.AreaStoreRequest::COORDINATE_LONGITUDE => ['required_with:'.self::FIELD_COORDINATES, 'numeric', 'between:-180,180'],
        ];
    }

    public function name(): ?string
    {
        return $this->filled(self::FIELD_NAME) ? $this->string(self::FIELD_NAME)->toString() : null;
    }

    public function coordinates(): ?array
    {
        return $this->filled(self::FIELD_COORDINATES) ? $this->input(self::FIELD_COORDINATES) : null;
    }
}
