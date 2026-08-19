<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class AvailablePaymentSystemsRequestDTO
{
    public string $billingReflectionAlias;

    public function setBillingReflectionAlias(string $billingReflectionAlias): self
    {
        $this->billingReflectionAlias = $billingReflectionAlias;

        return $this;
    }
}
