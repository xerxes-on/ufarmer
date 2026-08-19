<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class ConfirmPaymentRequestDTO extends BaseApiPaymentRequest
{
    public string $transactionId;

    public string $otp;

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function setOtp(string $otp): self
    {
        $this->otp = $otp;

        return $this;
    }
}
