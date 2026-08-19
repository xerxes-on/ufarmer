<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

use Makhweb\BillingClient\DTO\ClientResponseDTO;

class Response
{
    protected ClientResponseDTO $response;

    public function __construct(ClientResponseDTO $response)
    {
        $this->response = $response;
    }

    public function isError()
    {
        return $this->response->isError();
    }

    public function getErrorCode()
    {
        return data_get($this->response->getError(), 'code');
    }

    public function getErrorMessage()
    {
        return data_get($this->response->getError(), 'message');
    }
}
