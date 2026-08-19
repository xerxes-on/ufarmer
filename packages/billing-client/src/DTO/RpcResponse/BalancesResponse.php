<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

use Makhweb\BillingClient\DTO\BalanceDTO;

class BalancesResponse extends Response
{
    /**
     * Get all balances from the response.
     *
     * @return BalanceDTO[]
     */
    public function getBalances(): array
    {
        $balances = data_get($this->response->getResult(), 'data.balances', []);

        return array_map(
            fn (array $item) => BalanceDTO::fromArray($item),
            $balances
        );
    }

    /**
     * Get a single balance from the response.
     */
    public function getBalance(): ?BalanceDTO
    {
        $balance = data_get($this->response->getResult(), 'data.balance');

        if ($balance === null) {
            return null;
        }

        return BalanceDTO::fromArray($balance);
    }

    /**
     * Get the default balance from the list.
     */
    public function getDefaultBalance(): ?BalanceDTO
    {
        $balances = $this->getBalances();

        foreach ($balances as $balance) {
            if ($balance->isDefault) {
                return $balance;
            }
        }

        return $balances[0] ?? null;
    }
}
