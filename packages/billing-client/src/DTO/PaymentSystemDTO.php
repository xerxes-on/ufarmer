<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

class PaymentSystemDTO
{
    public string $name;

    public string $alias;

    public string $logo;

    public string $providerName;

    public string $providerType;

    public function __construct(
        string $name,
        string $alias,
        string $logo,
        string $providerName,
        string $providerType
    ) {
        $this->name = $name;
        $this->alias = $alias;
        $this->logo = $logo;
        $this->providerName = $providerName;
        $this->providerType = $providerType;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['alias'],
            $data['logo'],
            $data['providerName'],
            $data['providerType']
        );
    }
}
