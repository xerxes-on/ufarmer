<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Validators;

use Illuminate\Database\Eloquent\Model;
use Makhweb\BillingClient\DTO\TransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\AuthorizeRequestDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CheckTransactionRevertAllowedTransactionDTO;
use Xerxes\BillingClient\Contracts\PaymentValidatorInterface;
use Xerxes\BillingClient\DTOs\ValidationResultDTO;
use Xerxes\BillingClient\Enums\PaymentValidationError;
use Xerxes\BillingClient\Models\BillingServiceType;

abstract class AbstractPaymentValidator implements PaymentValidatorInterface
{
    public function validateAuthorization(
        BillingServiceType $serviceType,
        Model $entity,
        AuthorizeRequestDTO $request
    ): ValidationResultDTO {
        if (! $serviceType->isPaymentPendingStatus($entity)) {
            if ($serviceType->isPaidStatus($entity)) {
                return ValidationResultDTO::denied(
                    PaymentValidationError::AlreadyPaid
                );
            }

            return ValidationResultDTO::denied(
                PaymentValidationError::InvalidStatus
            );
        }

        $entityUserId = $serviceType->getEntityUserId($entity);
        $requestUserId = $request->userId();

        if ($entityUserId !== null && $requestUserId !== null) {
            if ((string) $entityUserId !== (string) $requestUserId) {
                return ValidationResultDTO::denied(
                    PaymentValidationError::UserMismatch
                );
            }
        }

        $cost = $serviceType->getEntityAmount($entity);
        $currency = $serviceType->getEntityCurrency($entity);

        if ($currency === null) {
            $currency = config('ufarm-billing-client.default_currency', 'UZS');
        }

        return ValidationResultDTO::allowed(
            cost: $cost,
            userId: (string) $entityUserId,
            currency: $currency,
        );
    }

    public function handleTransaction(
        BillingServiceType $serviceType,
        Model $entity,
        TransactionDTO $transaction
    ): ?int {
        return null;
    }

    public function canRevertTransaction(
        BillingServiceType $serviceType,
        Model $entity,
        CheckTransactionRevertAllowedTransactionDTO $request
    ): bool {
        if ($serviceType->isPaidStatus($entity)) {
            return true;
        }

        return (bool) ($serviceType->isPaymentPendingStatus($entity));
    }
}
