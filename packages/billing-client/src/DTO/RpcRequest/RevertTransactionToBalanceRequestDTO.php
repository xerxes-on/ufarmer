<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class RevertTransactionToBalanceRequestDTO
{
    public string $systemTransactionId;

    public string $balanceSystemAlias;

    public string $transactionSystemAlias;

    public function setSystemTransactionId(string $systemTransactionId): self
    {
        $this->systemTransactionId = $systemTransactionId;

        return $this;
    }

    public function setBalanceSystemAlias(string $balanceSystemAlias): self
    {
        $this->balanceSystemAlias = $balanceSystemAlias;

        return $this;
    }

    public function setTransactionSystemAlias(string $transactionSystemAlias): self
    {
        $this->transactionSystemAlias = $transactionSystemAlias;

        return $this;
    }
}
