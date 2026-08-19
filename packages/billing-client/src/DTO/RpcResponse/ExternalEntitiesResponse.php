<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

use Makhweb\BillingClient\DTO\BalanceDTO;
use Makhweb\BillingClient\DTO\BankCardDTO;

class ExternalEntitiesResponse extends Response
{
    /**
     * @return BalanceDTO
     */
    public function balance()
    {
        return $this->allBalances()[0] ?? null;
    }

    /**
     * @return BalanceDTO[]
     */
    public function allBalances()
    {
        return array_map(function ($item) {
            return BalanceDTO::fromArray($item);
        }, data_get($this->response->getResult(), 'data.entities', []));
    }

    /**
     * @return BankCardDTO[]
     */
    public function bankCards()
    {
        return array_map(function ($item) {
            return BankCardDTO::fromArray($item);
        }, data_get($this->response->getResult(), 'data.entities', []));
    }
}
