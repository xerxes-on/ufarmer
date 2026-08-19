<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\WebhookRequest;

use Makhweb\BillingClient\Http\Requests\AuthorizeRequest;

class AuthorizeRequestDTO
{
    public string $entityId;

    public string $paymentType;

    public string $paymentSystemCurrency;

    public array $meta;

    public function __construct(string $entityId, string $paymentType, string $paymentSystemCurrency, array $meta)
    {
        $this->entityId = $entityId;
        $this->paymentType = $paymentType;
        $this->paymentSystemCurrency = $paymentSystemCurrency;
        $this->meta = $meta;
    }

    public function balanceTypeAlias()
    {
        return data_get($this->meta, 'balance_type_alias');
    }

    public function balanceCurrency()
    {
        return data_get($this->meta, 'balance_currency');
    }

    public function userId()
    {
        return data_get($this->meta, 'user_id');
    }

    public function amount()
    {
        return data_get($this->meta, 'amount');
    }

    public function currencyMaxPrecision()
    {
        return data_get($this->meta, 'config.currency_max_precision', 14);
    }

    public static function fromRequest(AuthorizeRequest $request): self
    {
        return new self(
            $request->input('entity_id'),
            $request->input('payment_type'),
            $request->input('payment_system_currency'),
            $request->input('meta'),
        );
    }
}
