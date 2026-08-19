<?php

declare(strict_types=1);

namespace Ufarm\Premium\Observers;

use Ufarm\Premium\Models\Subscription;
use Ufarm\Premium\Services\SubscriptionNotifier;
use Ufarm\Premium\Support\PremiumAccess;

class SubscriptionObserver
{
    public function __construct(
        private readonly SubscriptionNotifier $notifier,
        private readonly PremiumAccess $premiumAccess
    ) {}

    public function created(Subscription $subscription): void
    {
        $this->clearCache($subscription);
        $this->notifier->created($subscription);
    }

    public function updated(Subscription $subscription): void
    {
        $this->clearCache($subscription);

        if ($subscription->wasChanged('renewed_at')) {
            $this->notifier->renewed($subscription);
        }

        if ($subscription->wasChanged('status')) {
            $previousStatus = $subscription->getOriginal('status');

            if ($previousStatus instanceof \UnitEnum) {
                $previousStatus = $previousStatus->value;
            }

            $previous = (string) $previousStatus;
            $this->notifier->statusChanged($subscription, $previous);
        }

        $this->notifier->updated($subscription);
    }

    public function deleted(Subscription $subscription): void
    {
        $this->clearCache($subscription);
        $this->notifier->deleted($subscription);
    }

    public function forceDeleted(Subscription $subscription): void
    {
        $this->clearCache($subscription);
        $this->notifier->deleted($subscription);
    }

    private function clearCache(Subscription $subscription): void
    {
        $this->premiumAccess->clearUserCache($subscription->user_id);
    }
}
