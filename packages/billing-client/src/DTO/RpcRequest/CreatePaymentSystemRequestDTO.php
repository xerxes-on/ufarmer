<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class CreatePaymentSystemRequestDTO
{
    public string $name;

    public string $alias;

    public string $driver;

    public int $minAmount;

    public int $maxAmount;

    public string $billingOrganizationAlias;

    public string $billingReflectionAlias;

    public array $credentials;

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

    public function setDriver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function setMinAmount(int $minAmount): self
    {
        $this->minAmount = $minAmount;

        return $this;
    }

    public function setMaxAmount(int $maxAmount): self
    {
        $this->maxAmount = $maxAmount;

        return $this;
    }

    public function setBillingOrganizationAlias(int $billingOrganizationAlias): self
    {
        $this->billingOrganizationAlias = $billingOrganizationAlias;

        return $this;
    }

    public function setBillingReflectionAlias(int $billingReflectionAlias): self
    {
        $this->billingReflectionAlias = $billingReflectionAlias;

        return $this;
    }

    public function setCredentials(array $credentials): self
    {
        $this->credentials = $credentials;

        return $this;
    }
}
