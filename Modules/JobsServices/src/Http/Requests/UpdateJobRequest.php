<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:job_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'price_mode' => ['sometimes', 'in:fixed,range'],
            'price_fixed' => ['required_if:price_mode,fixed', 'nullable', 'numeric', 'min:0'],
            'price_min' => ['required_if:price_mode,range', 'nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'timing_type' => ['sometimes', 'in:deadline,fixed'],
            'deadline' => ['required_if:timing_type,deadline', 'nullable', 'date', 'after:now'],
            'fixed_time' => ['required_if:timing_type,fixed', 'nullable', 'date', 'after:now'],
            'property_size' => ['sometimes', 'numeric', 'min:0'],
            'property_unit' => ['sometimes', 'string', 'max:50'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'region' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
