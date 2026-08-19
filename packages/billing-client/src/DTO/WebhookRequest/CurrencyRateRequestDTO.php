<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\WebhookRequest;

use Makhweb\BillingClient\Http\Requests\CurrencyRateRequest;

class CurrencyRateRequestDTO
{
    public string $entityCurrency;

    public string $paymentSystemCurrency;

    public string $paymentType;

    public array $meta;

    public function __construct(string $entityCurrency, string $paymentSystemCurrency, string $paymentType, array $meta)
    {
        $this->entityCurrency = $entityCurrency;
        $this->paymentSystemCurrency = $paymentSystemCurrency;
        $this->paymentType = $paymentType;
        $this->meta = $meta;
    }

    public static function fromRequest(CurrencyRateRequest $request)
    {
        return new self(
            $request->input('entity_currency'),
            $request->input('payment_system_currency'),
            $request->input('payment_type'),
            $request->input('meta'),
        );
    }

    public function entityId()
    {
        return data_get($this->meta, 'entity_id');
    }

    public function currencyMaxPrecision()
    {
        return data_get($this->meta, 'config.currency_max_precision', 14);
    }
}
