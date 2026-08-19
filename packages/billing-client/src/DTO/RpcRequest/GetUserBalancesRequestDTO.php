<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcRequest;

class GetUserBalancesRequestDTO
{
    public function __construct(
        public readonly int $ownerId,
        public readonly string $ownerType = 'users',
        public readonly ?string $balanceType = null
    ) {}
}
