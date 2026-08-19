<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\JobsServices\Enums\JobsServicesTranslationKey;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:job_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'price_mode' => ['required', 'in:fixed,range'],
            'price_fixed' => ['required_if:price_mode,fixed', 'nullable', 'numeric', 'min:0'],
            'price_min' => ['required_if:price_mode,range', 'nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timing_type' => ['required', 'in:deadline,fixed'],
            'deadline' => ['required_if:timing_type,deadline', 'nullable', 'date', 'after:now'],
            'fixed_time' => ['required_if:timing_type,fixed', 'nullable', 'date', 'after:now'],
            'property_size' => ['nullable', 'numeric', 'min:0'],
            'property_unit' => ['nullable', 'string', 'max:50'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'images' => ['nullable', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'price_fixed.required_if' => __(JobsServicesTranslationKey::ValidationPriceFixedRequired->value),
            'price_min.required_if' => __(JobsServicesTranslationKey::ValidationPriceMinRequired->value),
            'deadline.required_if' => __(JobsServicesTranslationKey::ValidationDeadlineRequired->value),
            'fixed_time.required_if' => __(JobsServicesTranslationKey::ValidationFixedTimeRequired->value),
            'images.*.max' => __(JobsServicesTranslationKey::ValidationImageMaxSize->value),
        ];
    }
}
