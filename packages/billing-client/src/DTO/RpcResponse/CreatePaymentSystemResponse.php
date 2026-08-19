<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

class CreatePaymentSystemResponse extends Response
{
    public function link()
    {
        return data_get($this->response->getResult(), 'data.id');
    }

    public function webhookPaymentUrl()
    {
        return data_get($this->response->getResult(), 'data.webhook.payment_url');
    }

    public function webhookValidationUrl()
    {
        return data_get($this->response->getResult(), 'data.webhook.validation_url');
    }
}
