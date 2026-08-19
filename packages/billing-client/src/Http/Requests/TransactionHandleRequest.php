<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionHandleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'system' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'system_transaction_id' => ['required', 'string'],
            'payment_type' => ['required', 'string'],
            'entity_id' => ['required', 'string'],
            'user_id' => ['required', 'string'],
            'amount' => ['nullable', 'integer'],
            'provider_type' => ['required', 'string'],
            'payment_system_currency' => ['required', 'string'],
            'state' => ['required', 'string'],
        ];
    }
}
