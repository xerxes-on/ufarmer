<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Makhweb\BillingClient\DTO\ClientResponseDTO;

class BillingRpcErrorOccurredEvent
{
    use Dispatchable;

    public ClientResponseDTO $responseDTO;

    public string $method;

    public array $params;

    public function __construct(ClientResponseDTO $responseDTO, string $method, array $params)
    {
        $this->responseDTO = $responseDTO;
        $this->method = $method;
        $this->params = $params;
    }
}
