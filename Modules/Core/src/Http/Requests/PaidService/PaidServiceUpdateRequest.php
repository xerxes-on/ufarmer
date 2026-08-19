<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\PaidService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Enums\PaidServiceStatus;

class PaidServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paidServiceId = $this->route('paidService')?->id;

        return [
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('paid_services', 'slug')->ignore($paidServiceId)],
            'name' => ['sometimes', 'array'],
            'name.en' => ['sometimes', 'string', 'max:255'],
            'name.uz' => ['nullable', 'string', 'max:255'],
            'name.ru' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.uz' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'is_paid' => ['sometimes', 'boolean'],
            'price' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'applicable_roles' => ['nullable', 'array'],
            'applicable_roles.*' => ['string', 'in:farmer,agronom'],
            'config' => ['nullable', 'array'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'status' => ['sometimes', Rule::enum(PaidServiceStatus::class)],
        ];
    }

    public function slug(): ?string
    {
        return $this->filled('slug') ? $this->string('slug')->toString() : null;
    }

    /**
     * @return array<string, string>|null
     */
    public function nameTranslations(): ?array
    {
        return $this->has('name') ? $this->input('name') : null;
    }

    /**
     * @return array<string, string>|null
     */
    public function descriptionTranslations(): ?array
    {
        return $this->has('description') ? $this->input('description') : null;
    }

    public function isPaid(): ?bool
    {
        return $this->has('is_paid') ? $this->boolean('is_paid') : null;
    }

    public function price(): ?int
    {
        return $this->filled('price') ? (int) $this->input('price') : null;
    }

    public function currency(): ?string
    {
        return $this->filled('currency') ? $this->string('currency')->toString() : null;
    }

    /**
     * @return array<int, string>|null
     */
    public function applicableRoles(): ?array
    {
        return $this->has('applicable_roles') ? $this->input('applicable_roles') : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function serviceConfig(): ?array
    {
        return $this->has('config') ? $this->input('config') : null;
    }

    public function validFrom(): ?string
    {
        return $this->has('valid_from') ? $this->input('valid_from') : null;
    }

    public function validUntil(): ?string
    {
        return $this->has('valid_until') ? $this->input('valid_until') : null;
    }

    public function status(): ?string
    {
        return $this->filled('status') ? $this->string('status')->toString() : null;
    }
}
