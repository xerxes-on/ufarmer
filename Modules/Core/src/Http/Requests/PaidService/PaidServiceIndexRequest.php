<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\PaidService;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Enums\PaidServiceStatus;

class PaidServiceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(PaidServiceStatus::cases(), 'value'))],
            'is_paid' => ['nullable', 'boolean'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }

    public function hasStatus(): bool
    {
        return $this->filled('status');
    }

    public function status(): ?string
    {
        return $this->input('status');
    }

    public function hasPaidFilter(): bool
    {
        return $this->has('is_paid');
    }

    public function isPaid(): bool
    {
        return $this->boolean('is_paid');
    }
}
