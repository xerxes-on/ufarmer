<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\MarketProductProposalReceivedEvent;
use App\Listeners\HandleMarketProductProposalListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        MarketProductProposalReceivedEvent::class => [
            HandleMarketProductProposalListener::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
