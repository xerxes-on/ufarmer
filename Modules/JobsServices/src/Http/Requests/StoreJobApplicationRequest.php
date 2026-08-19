<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required', 'exists:jobs,id'],
            'message' => ['nullable', 'string', 'max:1000'],
            'proposed_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'proposed_start_time' => ['nullable', 'date', 'after:now'],
            'estimated_duration' => ['nullable', 'integer', 'min:1', 'max:720'], // Max 30 days in hours
        ];
    }
}
