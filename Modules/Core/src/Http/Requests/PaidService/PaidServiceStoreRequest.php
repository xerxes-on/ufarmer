<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\PaidService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Enums\PaidServiceStatus;

class PaidServiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'unique:paid_services,slug'],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.uz' => ['nullable', 'string', 'max:255'],
            'name.ru' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.uz' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'is_paid' => ['required', 'boolean'],
            'price' => ['required_if:is_paid,true', 'nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'applicable_roles' => ['nullable', 'array'],
            'applicable_roles.*' => ['string', 'in:farmer,agronom'],
            'config' => ['nullable', 'array'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'status' => ['required', Rule::enum(PaidServiceStatus::class)],
        ];
    }

    public function slug(): string
    {
        return $this->string('slug')->toString();
    }

    /**
     * @return array<string, string>
     */
    public function nameTranslations(): array
    {
        return $this->input('name', []);
    }

    /**
     * @return array<string, string>|null
     */
    public function descriptionTranslations(): ?array
    {
        return $this->input('description');
    }

    public function isPaid(): bool
    {
        return $this->boolean('is_paid');
    }

    public function price(): ?int
    {
        return $this->filled('price') ? (int) $this->input('price') : null;
    }

    public function currency(): string
    {
        return $this->string('currency', 'UZS')->toString();
    }

    /**
     * @return array<int, string>|null
     */
    public function applicableRoles(): ?array
    {
        return $this->input('applicable_roles');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function serviceConfig(): ?array
    {
        return $this->input('config');
    }

    public function validFrom(): ?string
    {
        return $this->input('valid_from');
    }

    public function validUntil(): ?string
    {
        return $this->input('valid_until');
    }

    public function status(): string
    {
        return $this->string('status')->toString();
    }
}
