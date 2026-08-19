<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:service_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'price_unit' => ['sometimes', 'string', 'in:per_hour,per_km,per_ar,per_m2,per_item,per_day,per_project'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'availability' => ['sometimes', 'array'],
            'timing_description' => ['sometimes', 'string', 'max:500'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'region' => ['sometimes', 'string', 'max:100'],
            'service_radius' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
