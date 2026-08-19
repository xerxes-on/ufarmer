<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

use Makhweb\BillingClient\Http\Requests\TransactionHandleRequest;

class TransactionDTO
{
    public string $system;

    public string $transactionId;

    public string $systemTransactionId;

    public string $paymentType;

    public string $entityId;

    public string $userId;

    public float $amount;

    public string $providerType;

    public string $paymentSystemCurrency;

    public string $state;

    public function __construct(string $system, string $transactionId, string $systemTransactionId, string $paymentType, string $entityId, string $userId, float $amount, string $providerType, string $paymentSystemCurrency, string $state)
    {
        $this->system = $system;
        $this->transactionId = $transactionId;
        $this->systemTransactionId = $systemTransactionId;
        $this->paymentType = $paymentType;
        $this->entityId = $entityId;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->providerType = $providerType;
        $this->paymentSystemCurrency = $paymentSystemCurrency;
        $this->state = $state;
    }

    public static function fromRequest(TransactionHandleRequest $request): self
    {
        return new self(
            $request->input('system'),
            $request->input('transaction_id'),
            $request->input('system_transaction_id'),
            $request->input('payment_type'),
            $request->input('entity_id'),
            $request->input('user_id'),
            $request->input('amount'),
            $request->input('provider_type'),
            $request->input('payment_system_currency'),
            $request->input('state'),
        );
    }
}
