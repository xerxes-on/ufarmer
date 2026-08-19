<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class AreaCalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            AreaStoreRequest::FIELD_COORDINATES => ['required', 'array', 'min:3'],
            AreaStoreRequest::FIELD_COORDINATES.'.*' => ['required', 'array', 'size:2'],
            AreaStoreRequest::FIELD_COORDINATES.'.*.'.AreaStoreRequest::COORDINATE_LATITUDE => ['required', 'numeric', 'between:-90,90'],
            AreaStoreRequest::FIELD_COORDINATES.'.*.'.AreaStoreRequest::COORDINATE_LONGITUDE => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function coordinates(): array
    {
        return $this->input(AreaStoreRequest::FIELD_COORDINATES, []);
    }
}
