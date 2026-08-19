<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

class PaymentResponse extends Response
{
    public function otpSent()
    {
        return data_get($this->response->getResult(), 'data.otp');
    }

    public function transactionId()
    {
        return data_get($this->response->getResult(), 'data.transaction_id');
    }

    public function otpPhoneNumber()
    {
        return data_get($this->response->getResult(), 'data.otp_phone_number');
    }
}
