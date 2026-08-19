<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class BaseApiPaymentRequest
{
    public string $billingReflectionAlias;

    public string $paymentSystemAlias;

    public string $userId;

    public function setBillingReflectionAlias(string $billingReflectionAlias): self
    {
        $this->billingReflectionAlias = $billingReflectionAlias;

        return $this;
    }

    public function setPaymentSystemAlias(string $paymentSystemAlias): self
    {
        $this->paymentSystemAlias = $paymentSystemAlias;

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
