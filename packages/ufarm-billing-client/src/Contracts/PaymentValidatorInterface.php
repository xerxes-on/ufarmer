<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Contracts;

use Illuminate\Database\Eloquent\Model;
use Makhweb\BillingClient\DTO\TransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\AuthorizeRequestDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CheckTransactionRevertAllowedTransactionDTO;
use Xerxes\BillingClient\DTOs\ValidationResultDTO;
use Xerxes\BillingClient\Models\BillingServiceType;

interface PaymentValidatorInterface
{
    /**
     * Validate authorization request.
     */
    public function validateAuthorization(
        BillingServiceType $serviceType,
        Model $entity,
        AuthorizeRequestDTO $request
    ): ValidationResultDTO;

    /**
     * Handle transaction callback (payment completed).
     * Returns the local transaction/payment ID if created.
     */
    public function handleTransaction(
        BillingServiceType $serviceType,
        Model $entity,
        TransactionDTO $transaction
    ): ?int;

    /**
     * Check if a transaction can be reverted (refunded).
     */
    public function canRevertTransaction(
        BillingServiceType $serviceType,
        Model $entity,
        CheckTransactionRevertAllowedTransactionDTO $request
    ): bool;
}
