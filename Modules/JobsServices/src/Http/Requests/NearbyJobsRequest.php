<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NearbyJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function latitude(): float
    {
        return (float) $this->input('latitude');
    }

    public function longitude(): float
    {
        return (float) $this->input('longitude');
    }

    public function radius(): float
    {
        return (float) $this->input('radius', 10);
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 20);
    }
}
