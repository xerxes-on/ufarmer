<?php

declare(strict_types=1);

namespace App\Events;

use Xerxes\RabbitMQ\Support\ShouldPublish;

class ProposalRejectedEvent implements ShouldPublish
{
    public string $exchange = 'admin.events';

    public function __construct(
        public int $proposalId,
        public string $rejectionReason,
    ) {}

    public function routingKey(): string
    {
        return 'admin.proposal.rejected';
    }
}
