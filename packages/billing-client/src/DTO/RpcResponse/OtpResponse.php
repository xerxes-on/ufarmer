<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

class OtpResponse extends Response
{
    public function externalEntityId()
    {
        return data_get($this->response->getError(), 'meta.external_entity_id');
    }
}
