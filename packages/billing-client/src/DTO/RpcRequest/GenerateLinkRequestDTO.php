<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class GenerateLinkRequestDTO
{
    public int $entityId;

    public float $amount;

    public string $paymentSystemAlias;

    public string $billingReflectionAlias;

    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setPaymentSystemAlias(string $paymentSystemAlias): self
    {
        $this->paymentSystemAlias = $paymentSystemAlias;

        return $this;
    }

    public function setBillingReflectionAlias(string $billingReflectionAlias): self
    {
        $this->billingReflectionAlias = $billingReflectionAlias;

        return $this;
    }
}
