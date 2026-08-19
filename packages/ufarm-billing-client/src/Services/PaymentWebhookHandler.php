<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Makhweb\BillingClient\Contracts\PaymentWebhookContract;
use Makhweb\BillingClient\DTO\CurrencyRateDTO;
use Makhweb\BillingClient\DTO\FloatFixerDTO;
use Makhweb\BillingClient\DTO\TransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\AuthorizeRequestDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CheckTransactionRevertAllowedTransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CurrencyRateRequestDTO;
use Makhweb\BillingClient\DTO\WebhookResponse\AuthorizeResponseDTO;
use Makhweb\BillingClient\DTO\WebhookResponse\TransactionRevertAllowedResponseDTO;
use Xerxes\BillingClient\Contracts\EntityParserInterface;
use Xerxes\BillingClient\Enums\PaymentValidationError;
use Xerxes\BillingClient\Models\BillingServiceType;

class PaymentWebhookHandler implements PaymentWebhookContract
{
    public function __construct(
        private readonly EntityParserInterface $entityParser,
    ) {}

    public function authorize(AuthorizeRequestDTO $DTO): AuthorizeResponseDTO
    {
        $response = new AuthorizeResponseDTO;

        try {
            $parsedEntity = $this->entityParser->parse($DTO->entityId);
            $serviceType = BillingServiceType::findByAlias($parsedEntity->serviceTypeAlias);

            if ($serviceType === null) {
                $this->logWarning('Unknown service type', [
                    'entity_id' => $DTO->entityId,
                    'service_type_alias' => $parsedEntity->serviceTypeAlias,
                ]);

                return $response
                    ->setAllowed(false)
                    ->setErrorCode(PaymentValidationError::UnknownServiceType->value)
                    ->setErrorMessage(PaymentValidationError::UnknownServiceType->message());
            }

            $entity = $serviceType->findEntity($parsedEntity->getNumericId());

            if ($entity === null) {
                $this->logWarning('Entity not found', [
                    'entity_id' => $DTO->entityId,
                    'numeric_id' => $parsedEntity->getNumericId(),
                ]);

                return $response
                    ->setAllowed(false)
                    ->setErrorCode(PaymentValidationError::EntityNotFound->value)
                    ->setErrorMessage(PaymentValidationError::EntityNotFound->message());
            }

            $validator = $serviceType->resolveValidator();
            $result = $validator->validateAuthorization($serviceType, $entity, $DTO);

            if ($result->isDenied()) {
                $this->logWarning('Authorization denied', [
                    'entity_id' => $DTO->entityId,
                    'error_code' => $result->errorCode?->value,
                    'error_message' => $result->errorMessage,
                ]);

                return $response
                    ->setAllowed(false)
                    ->setErrorCode($result->errorCode?->value)
                    ->setErrorMessage($result->errorMessage);
            }

            $this->logInfo('Authorization allowed', [
                'entity_id' => $DTO->entityId,
                'cost' => $result->cost,
                'currency' => $result->currency,
            ]);

            return $response
                ->setAllowed(true)
                ->setCost($result->cost)
                ->setUserId($result->userId)
                ->setEntityCurrency($result->currency);

        } catch (InvalidArgumentException $e) {
            $this->logWarning('Invalid entity format', [
                'entity_id' => $DTO->entityId,
                'error' => $e->getMessage(),
            ]);

            return $response
                ->setAllowed(false)
                ->setErrorCode(PaymentValidationError::InvalidEntityFormat->value)
                ->setErrorMessage($e->getMessage());
        }
    }

    public function handleTransaction(TransactionDTO $DTO): ?int
    {
        try {
            $parsedEntity = $this->entityParser->parse($DTO->entityId);
            $serviceType = BillingServiceType::findByAlias($parsedEntity->serviceTypeAlias);

            if ($serviceType === null) {
                $this->logError('Transaction handling failed - unknown service type', [
                    'entity_id' => $DTO->entityId,
                    'transaction_id' => $DTO->transactionId,
                ]);

                return null;
            }

            $entity = $serviceType->findEntity($parsedEntity->getNumericId());

            if ($entity === null) {
                $this->logError('Transaction handling failed - entity not found', [
                    'entity_id' => $DTO->entityId,
                    'transaction_id' => $DTO->transactionId,
                ]);

                return null;
            }

            $validator = $serviceType->resolveValidator();
            $result = $validator->handleTransaction($serviceType, $entity, $DTO);

            $this->logInfo('Transaction handled', [
                'entity_id' => $DTO->entityId,
                'transaction_id' => $DTO->transactionId,
                'state' => $DTO->state,
                'result' => $result,
            ]);

            return $result;

        } catch (InvalidArgumentException $e) {
            $this->logError('Transaction handling failed - invalid entity format', [
                'entity_id' => $DTO->entityId,
                'transaction_id' => $DTO->transactionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function checkTransactionRevertAllowed(
        CheckTransactionRevertAllowedTransactionDTO $DTO
    ): TransactionRevertAllowedResponseDTO {
        $allowed = $DTO->currentState === 'commited'
            && $DTO->newState === 'reverted_to_balance';

        return new TransactionRevertAllowedResponseDTO(
            allowed: $allowed,
            message: $allowed ? null : 'Transaction cannot be reverted in current state.'
        );
    }

    public function currencyRate(CurrencyRateRequestDTO $DTO): CurrencyRateDTO
    {
        $precision = $DTO->currencyMaxPrecision();

        $entityToProvider = (new FloatFixerDTO)
            ->setRate(1.0)
            ->setScale($precision)
            ->setRoundingMode(FloatFixerDTO::ROUNDING_MODE_UP);

        $providerToEntity = (new FloatFixerDTO)
            ->setRate(1.0)
            ->setScale($precision)
            ->setRoundingMode(FloatFixerDTO::ROUNDING_MODE_DOWN);

        return (new CurrencyRateDTO)
            ->setEntityToPaymentProviderFixer($entityToProvider)
            ->setPaymentProviderToEntityFixer($providerToEntity);
    }

    private function logInfo(string $message, array $context = []): void
    {
        if (config('ufarm-billing-client.logging.enabled', true)) {
            Log::channel(config('ufarm-billing-client.logging.channel', 'stack'))
                ->info("[UfarmBillingClient] {$message}", $context);
        }
    }

    private function logWarning(string $message, array $context = []): void
    {
        if (config('ufarm-billing-client.logging.enabled', true)) {
            Log::channel(config('ufarm-billing-client.logging.channel', 'stack'))
                ->warning("[UfarmBillingClient] {$message}", $context);
        }
    }

    private function logError(string $message, array $context = []): void
    {
        if (config('ufarm-billing-client.logging.enabled', true)) {
            Log::channel(config('ufarm-billing-client.logging.channel', 'stack'))
                ->error("[UfarmBillingClient] {$message}", $context);
        }
    }
}
