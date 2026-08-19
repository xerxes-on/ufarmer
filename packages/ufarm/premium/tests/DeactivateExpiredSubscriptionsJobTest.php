<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Core\app\Models\User;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Jobs\DeactivateExpiredSubscriptionsJob;
use Ufarm\Premium\Models\PremiumPlan;
use Ufarm\Premium\Models\Subscription;

it('marks past due subscriptions as expired', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Plan Expire']);
    $user = User::query()->create(['name' => 'Expiring User']);

    $subscription = Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'ends_at' => CarbonImmutable::now()->subDay(),
    ]);

    (new DeactivateExpiredSubscriptionsJob)->handle();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::EXPIRED);
});

it('ignores active subscriptions without expiry', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Plan Active']);
    $user = User::query()->create(['name' => 'No Expiry User']);

    $subscription = Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'ends_at' => null,
    ]);

    (new DeactivateExpiredSubscriptionsJob)->handle();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::ACTIVE);
});
