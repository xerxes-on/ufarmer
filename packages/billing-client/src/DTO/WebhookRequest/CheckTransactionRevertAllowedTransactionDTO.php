<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\WebhookRequest;

use Makhweb\BillingClient\Http\Requests\TransactionRevertAllowedRequest;

class CheckTransactionRevertAllowedTransactionDTO
{
    public string $paymentType;

    public string $transactionId;

    public string $system;

    public string $systemTransactionId;

    public string $currentState;

    public string $newState;

    public function __construct(string $paymentType, string $transactionId, string $system, string $systemTransactionId, string $currentState, string $newState)
    {
        $this->paymentType = $paymentType;
        $this->transactionId = $transactionId;
        $this->system = $system;
        $this->systemTransactionId = $systemTransactionId;
        $this->currentState = $currentState;
        $this->newState = $newState;
    }

    public static function fromRequest(TransactionRevertAllowedRequest $request): self
    {
        return new self(
            $request->input('payment_type'),
            $request->input('transaction_id'),
            $request->input('system'),
            $request->input('system_transaction_id'),
            $request->input('current_state'),
            $request->input('new_state'),
        );
    }
}
