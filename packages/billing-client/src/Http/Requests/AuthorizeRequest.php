<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'string'],
            'payment_type' => ['required', 'string'],
            'payment_system_currency' => ['required', 'string'],
            'meta' => ['required', 'array'],
        ];
    }
}
