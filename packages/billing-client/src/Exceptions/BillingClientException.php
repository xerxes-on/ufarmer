<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Exceptions;

use Exception;

class BillingClientException extends Exception
{
    const USER_DEBT_ALREADY_CREATED = 'user_debt_already_created';

    private string $rpcErrorCode;

    private string $rpcErrorMessage;

    public static function fromRpcError(string $rpcErrorCode, string $rpcErrorMessage): self
    {
        $exception = new self;
        $exception->rpcErrorCode = $rpcErrorCode;
        $exception->rpcErrorMessage = $rpcErrorMessage;

        return $exception;
    }

    public function getRpcErrorCode(): string
    {
        return $this->rpcErrorCode;
    }

    public function getRpcErrorMessage(): string
    {
        return $this->rpcErrorMessage;
    }
}
