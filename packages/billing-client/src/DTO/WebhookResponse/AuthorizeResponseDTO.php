<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\WebhookResponse;

use Makhweb\BillingClient\DTO\CurrencyRateDTO;

class AuthorizeResponseDTO
{
    public bool $allowed;

    public ?CurrencyRateDTO $currencyRateDTO = null;

    public ?string $errorCode = null;

    public ?string $errorMessage = null;

    public array $data = [];

    public function setCurrencyRateDTO(?CurrencyRateDTO $currencyRateDTO): self
    {
        $this->currencyRateDTO = $currencyRateDTO;

        return $this;
    }

    public function setAllowed(bool $allowed): self
    {
        $this->allowed = $allowed;

        return $this;
    }

    public function setErrorCode(?string $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function setCost(?float $cost): self
    {
        $this->data['cost'] = $cost;

        return $this;
    }

    public function setUserId(?string $userId): self
    {
        $this->data['user_id'] = $userId;

        return $this;
    }

    public function setRecipientBillingOrganizationAlias(string $recipientBillingOrganizationAlias): self
    {
        $this->data['recipient_billing_organization_alias'] = $recipientBillingOrganizationAlias;

        return $this;
    }

    public function setEntityCurrency(?string $entityCurrency): self
    {
        $this->data['entity_currency'] = $entityCurrency;

        return $this;
    }
}
