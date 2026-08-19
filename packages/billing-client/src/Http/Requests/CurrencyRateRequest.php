<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurrencyRateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_system_currency' => ['required', 'string'],
            'entity_currency' => ['required', 'string'],
            'meta' => ['required', 'array'],
            'payment_type' => ['required', 'string'],
        ];
    }
}
