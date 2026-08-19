<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AgroPrices\Enums\AgroPricesTranslationKey;

class GetTodayPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lang' => ['nullable', 'string', 'in:uz,ru,en'],
        ];
    }

    public function messages(): array
    {
        return [
            'lang.in' => __(AgroPricesTranslationKey::ValidationLangInvalid->value),
        ];
    }

    public function getLang(): string
    {
        return $this->query('lang', 'uz');
    }
}
