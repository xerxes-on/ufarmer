<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

use Sajya\Client\Response as RpcResponse;

class ClientResponseDTO
{
    private RpcResponse $raw;

    public function __construct(RpcResponse $response)
    {
        $this->raw = $response;
    }

    public function getRequestId()
    {
        return data_get($this->raw->result(), 'request_id');
    }

    public function getResult()
    {
        return $this->raw->result();
    }

    public function getError()
    {
        return $this->raw->error();
    }

    public function getRawResponse()
    {
        return $this->raw;
    }

    public function isError()
    {
        return $this->raw->error() || ! data_get($this->getResult(), 'success', false);
    }
}
