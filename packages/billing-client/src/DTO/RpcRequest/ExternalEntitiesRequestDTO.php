<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class ExternalEntitiesRequestDTO
{
    public string $paymentSystemAlias;

    public string $userId;

    public bool $fetchBalances = true;

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

    public function setFetchBalances(bool $fetchBalances): self
    {
        $this->fetchBalances = $fetchBalances;

        return $this;
    }
}
