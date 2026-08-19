<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class SendOtpRequestDTO extends BaseApiPaymentRequest
{
    public string $pan;

    public string $expiry;

    public string $phone;

    public function setPan(string $pan): self
    {
        $this->pan = $pan;

        return $this;
    }

    public function setExpiry(string $expiry): self
    {
        $this->expiry = $expiry;

        return $this;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }
}
