<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class SetDefaultExternalEntityRequestDTO
{
    public string $externalEntityId;

    public string $paymentSystemAlias;

    public string $userId;

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

    public function setExternalEntityId(string $externalEntityId): self
    {
        $this->externalEntityId = $externalEntityId;

        return $this;
    }
}
