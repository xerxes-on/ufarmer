<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\JobsServices\Enums\JobsServicesTranslationKey;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:service_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', 'string', 'in:per_hour,per_km,per_ar,per_m2,per_item,per_day,per_project'],
            'currency' => ['nullable', 'string', 'size:3'],
            'availability' => ['nullable', 'array'],
            'timing_description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'service_radius' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.max' => __(JobsServicesTranslationKey::ValidationImageMaxSize->value),
        ];
    }
}
