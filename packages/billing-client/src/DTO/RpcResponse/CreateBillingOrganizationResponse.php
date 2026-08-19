<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

class CreateBillingOrganizationResponse extends Response
{
    public function link()
    {
        return data_get($this->response->getResult(), 'data.id');
    }
}
