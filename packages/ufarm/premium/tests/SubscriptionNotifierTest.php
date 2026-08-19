<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Core\app\Models\User;
use Ufarm\Premium\Models\PremiumPlan;
use Ufarm\Premium\Models\Subscription;

class FakeRabbitClient
{
    public array $messages = [];

    public function publish(string $exchange, string $routingKey, array|string $payload, array $options = []): void
    {
        $this->messages[] = compact('exchange', 'routingKey', 'payload', 'options');
    }
}

beforeEach(function (): void {
    $fake = new FakeRabbitClient;
    app()->instance('rabbitmq.client', $fake);
    $this->fakeRabbit = $fake;
});

it('publishes lifecycle events for subscriptions', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Lifecycle']);
    $user = User::query()->create(['name' => 'Lifecycle User']);

    $subscription = Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'ends_at' => CarbonImmutable::now()->addMonth(),
    ]);

    expect($this->fakeRabbit->messages)->toHaveCount(1)
        ->and($this->fakeRabbit->messages[0]['payload']['event'])->toBe('premium.subscription.created');

    $this->fakeRabbit->messages = [];

    $subscription->renew(CarbonImmutable::now()->addMonths(2));

    expect($this->fakeRabbit->messages)->toHaveCount(2)
        ->and($this->fakeRabbit->messages[0]['payload']['event'])->toBe('premium.subscription.renewed')
        ->and($this->fakeRabbit->messages[1]['payload']['event'])->toBe('premium.subscription.updated');

    $this->fakeRabbit->messages = [];

    $subscription->markExpired();

    expect($this->fakeRabbit->messages)->toHaveCount(2)
        ->and($this->fakeRabbit->messages[0]['payload']['event'])->toBe('premium.subscription.status_changed')
        ->and($this->fakeRabbit->messages[0]['payload']['previous_status'])->toBe('active')
        ->and($this->fakeRabbit->messages[1]['payload']['event'])->toBe('premium.subscription.updated');

    $this->fakeRabbit->messages = [];

    $subscription->delete();

    expect($this->fakeRabbit->messages)->toHaveCount(1)
        ->and($this->fakeRabbit->messages[0]['payload']['event'])->toBe('premium.subscription.deleted');
});
