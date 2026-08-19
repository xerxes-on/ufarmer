<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

class PaymentSystemsDriversResponse extends Response
{
    /**
     * @return string[]
     */
    public function drivers()
    {
        return data_get($this->response->getResult(), 'data.drivers', []);
    }
}
