<?php

declare(strict_types=1);

namespace App\Events;

use Xerxes\RabbitMQ\Support\ShouldPublish;

class CreateMarketProductFromProposalEvent implements ShouldPublish
{
    public string $exchange = 'admin.events';

    public function __construct(
        public int $proposalId,
        public array $productData,
        public array $treatmentData,
    ) {}

    public function routingKey(): string
    {
        return 'admin.market_product.create_from_proposal';
    }
}
