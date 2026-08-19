<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\DTOs;

use Xerxes\BillingClient\Enums\PaymentValidationError;

final readonly class ValidationResultDTO
{
    private function __construct(
        public bool $allowed,
        public ?float $cost = null,
        public ?string $userId = null,
        public ?string $currency = null,
        public ?PaymentValidationError $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function allowed(
        float $cost,
        string $userId,
        string $currency
    ): self {
        return new self(
            allowed: true,
            cost: $cost,
            userId: $userId,
            currency: $currency,
        );
    }

    public static function denied(
        PaymentValidationError $errorCode,
        ?string $errorMessage = null
    ): self {
        return new self(
            allowed: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage ?? $errorCode->message(),
        );
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isDenied(): bool
    {
        return ! $this->allowed;
    }
}
