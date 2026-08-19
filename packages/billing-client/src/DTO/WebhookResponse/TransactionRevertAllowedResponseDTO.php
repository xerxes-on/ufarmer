<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\WebhookResponse;

class TransactionRevertAllowedResponseDTO
{
    public bool $allowed;

    public ?string $message;

    public function __construct(bool $allowed, ?string $message)
    {
        $this->allowed = $allowed;
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'message' => $this->message,
        ];
    }
}
