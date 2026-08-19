<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Contracts;

use Makhweb\BillingClient\DTO\CurrencyRateDTO;
use Makhweb\BillingClient\DTO\TransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\AuthorizeRequestDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CheckTransactionRevertAllowedTransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CurrencyRateRequestDTO;
use Makhweb\BillingClient\DTO\WebhookResponse\AuthorizeResponseDTO;
use Makhweb\BillingClient\DTO\WebhookResponse\TransactionRevertAllowedResponseDTO;

interface PaymentWebhookContract
{
    public function handleTransaction(TransactionDTO $DTO): ?int;

    public function authorize(AuthorizeRequestDTO $DTO): AuthorizeResponseDTO;

    public function checkTransactionRevertAllowed(CheckTransactionRevertAllowedTransactionDTO $DTO): TransactionRevertAllowedResponseDTO;

    public function currencyRate(CurrencyRateRequestDTO $DTO): CurrencyRateDTO;
}
