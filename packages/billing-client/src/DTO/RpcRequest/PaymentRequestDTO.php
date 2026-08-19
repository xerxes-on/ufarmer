<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class PaymentRequestDTO extends BaseApiPaymentRequest
{
    public string $paymentEntityId;

    public string $entityId;

    public float $amount;

    public bool $processWithoutConfirmation = false;

    public function setPaymentEntityId(string $paymentEntityId): self
    {
        $this->paymentEntityId = $paymentEntityId;

        return $this;
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setProcessWithoutConfirmation(bool $processWithoutConfirmation): self
    {
        $this->processWithoutConfirmation = $processWithoutConfirmation;

        return $this;
    }
}
