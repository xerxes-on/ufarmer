<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class CreateBillingReflectionRequestDTO
{
    public string $name;

    public string $alias;

    public string $paymentType;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function setPaymentType(string $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }
}
