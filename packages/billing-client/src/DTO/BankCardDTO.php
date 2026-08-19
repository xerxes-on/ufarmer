<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

class BankCardDTO
{
    public string $id;

    public string $name;

    public string $number;

    public string $expireDate;

    public float $amount;

    public string $phone;

    public string $currency;

    public bool $isDefault;

    public function __construct(
        string $id,
        string $name,
        string $number,
        string $expireDate,
        float $amount,
        string $phone,
        string $currency,
        bool $isDefault
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->number = $number;
        $this->expireDate = $expireDate;
        $this->amount = $amount;
        $this->phone = $phone;
        $this->currency = $currency;
        $this->isDefault = $isDefault;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['number'],
            $data['expire_date'],
            $data['amount'],
            $data['phone'],
            $data['currency'],
            $data['is_default'],
        );
    }
}
