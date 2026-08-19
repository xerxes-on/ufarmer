<?php

declare(strict_types=1);

namespace Ufarm\Premium\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Models\Subscription;

class DeactivateExpiredSubscriptionsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function uniqueId(): string
    {
        return 'premium:deactivate-expired';
    }

    public function handle(): void
    {
        Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', CarbonImmutable::now())
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $subscription->markExpired();
                }
            });
    }
}
