<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

class BalanceDTO
{
    public function __construct(
        public readonly string $id,
        public readonly int $amount,
        public readonly ?string $type,
        public readonly ?string $name,
        public readonly string $currency,
        public readonly bool $isDefault
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            amount: (int) ($data['amount'] ?? 0),
            type: $data['type'] ?? null,
            name: $data['name'] ?? null,
            currency: $data['currency'] ?? 'UZS',
            isDefault: (bool) ($data['is_default'] ?? false)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'type' => $this->type,
            'name' => $this->name,
            'currency' => $this->currency,
            'is_default' => $this->isDefault,
        ];
    }
}
