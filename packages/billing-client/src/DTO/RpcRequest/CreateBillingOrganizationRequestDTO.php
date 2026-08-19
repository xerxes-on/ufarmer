<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class CreateBillingOrganizationRequestDTO
{
    public string $name;

    public string $alias;

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
}
