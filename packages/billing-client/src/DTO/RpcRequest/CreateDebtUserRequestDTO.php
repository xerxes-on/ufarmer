<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class CreateDebtUserRequestDTO
{
    public string $userId;

    public string $passport;

    public ?string $pinfl;

    public string $firstName;

    public string $lastName;

    public string $middleName;

    public array $phoneNumbers;

    public string $entityId;

    public ?string $bankCardId;

    public string $paymentStartAt;

    public string $billingReflectionAlias;

    public int $initialDebtSum;

    public function setInitialDebtSum(int $initialDebtSum): self
    {
        $this->initialDebtSum = $initialDebtSum;

        return $this;
    }

    public function setPaymentStartAt(string $paymentStartAt): self
    {
        $this->paymentStartAt = $paymentStartAt;

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setPassport(string $passport): self
    {
        $this->passport = $passport;

        return $this;
    }

    public function setPinfl(?string $pinfl): self
    {
        $this->pinfl = $pinfl;

        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setMiddleName(string $middleName): self
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function setPhoneNumbers(array $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;

        return $this;
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function setBankCardId(?string $bankCardId): self
    {
        $this->bankCardId = $bankCardId;

        return $this;
    }

    public function setBillingReflectionAlias(string $billingReflectionAlias): self
    {
        $this->billingReflectionAlias = $billingReflectionAlias;

        return $this;
    }
}
