<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class CreateBalanceRequestDTO
{
    public array $paymentSystemAliases;

    public string $userId;

    public function setPaymentSystemAliases(array $paymentSystemAliases): self
    {
        $this->paymentSystemAliases = $paymentSystemAliases;

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
