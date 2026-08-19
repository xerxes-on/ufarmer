<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

class CurrencyRateDTO
{
    public FloatFixerDTO $entityToPaymentProviderFixer;

    public FloatFixerDTO $paymentProviderToEntityFixer;

    public function setEntityToPaymentProviderFixer(FloatFixerDTO $entityToPaymentProviderFixer): self
    {
        $this->entityToPaymentProviderFixer = $entityToPaymentProviderFixer;

        return $this;
    }

    public function setPaymentProviderToEntityFixer(FloatFixerDTO $paymentProviderToEntityFixer): self
    {
        $this->paymentProviderToEntityFixer = $paymentProviderToEntityFixer;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'fixers' => [
                'entity_to_paymentProvider' => $this->entityToPaymentProviderFixer->toArray(),
                'paymentProvider_to_entity' => $this->paymentProviderToEntityFixer->toArray(),
            ],
        ];
    }
}
