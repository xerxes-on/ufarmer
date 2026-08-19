<?php

declare(strict_types=1);

namespace Ufarm\Premium\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Models\Subscription;

class SubscriptionNotifier
{
    private object $rabbitClient;

    public function __construct(?object $rabbitClient = null)
    {
        $this->rabbitClient = $rabbitClient ?? app('rabbitmq.client');
    }

    public function created(Subscription $subscription): void
    {
        $this->publish('created', $subscription);
    }

    public function updated(Subscription $subscription): void
    {
        $this->publish('updated', $subscription);
    }

    public function renewed(Subscription $subscription): void
    {
        $this->publish('renewed', $subscription);
    }

    public function statusChanged(Subscription $subscription, string $previousStatus): void
    {
        $this->publish('status_changed', $subscription, ['previous_status' => $previousStatus]);
    }

    public function deleted(Subscription $subscription): void
    {
        $this->publish('deleted', $subscription);
    }

    private function publish(string $event, Subscription $subscription, array $additional = []): void
    {
        $exchange = config('premium.rabbitmq.exchange', 'premium');
        $routingKey = config('premium.rabbitmq.queue', 'premium.subscriptions');

        $payload = array_merge([
            'event' => "premium.subscription.{$event}",
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'subscription' => $this->formatSubscription($subscription),
        ], $additional);

        try {
            // Publish message to RabbitMQ
            $this->rabbitClient->publish(
                exchange: $exchange,
                routingKey: $routingKey,
                payload: $payload
            );
        } catch (Throwable $exception) {
            Log::warning('Failed to publish premium subscription event', [
                'event' => $event,
                'subscription_id' => $subscription->id,
                'exchange' => $exchange,
                'routing_key' => $routingKey,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function formatSubscription(Subscription $subscription): array
    {
        $subscription->loadMissing('plan');

        return [
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan' => $subscription->plan?->only(['id', 'name', 'application_id']),
            'status' => $subscription->status instanceof SubscriptionStatus
                ? $subscription->status->value
                : $subscription->status,
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'canceled_at' => $subscription->canceled_at?->toIso8601String(),
            'renewed_at' => $subscription->renewed_at?->toIso8601String(),
            'application_id' => $subscription->application_id,
        ];
    }
}
