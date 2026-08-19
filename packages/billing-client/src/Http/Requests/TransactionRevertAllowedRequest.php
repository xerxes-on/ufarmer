<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRevertAllowedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_type' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'system' => ['required', 'string'],
            'system_transaction_id' => ['required', 'string'],
            'current_state' => ['required', 'string'],
            'new_state' => ['required', 'string'],
        ];
    }
}
