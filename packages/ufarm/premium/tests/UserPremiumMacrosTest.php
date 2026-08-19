<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\app\Models\User;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Models\PremiumPlan;
use Ufarm\Premium\Models\Subscription;
use Ufarm\Premium\Support\PremiumAccess;

it('exposes premium relationships on the user model', function (): void {
    $user = User::query()->create(['name' => 'Relation User']);

    expect($user->premiumPlans())->toBeInstanceOf(BelongsToMany::class)
        ->and($user->premiumSubscriptions())->toBeInstanceOf(HasMany::class);
});

it('detects active premium subscriptions through the access helper', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Macro Test']);
    $user = User::query()->create(['name' => 'Macro Active User']);

    Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'ends_at' => CarbonImmutable::now()->addDay(),
    ]);

    expect(app(PremiumAccess::class)->userHasActive($user))->toBeTrue();
});

it('returns false for expired subscriptions', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Macro Expired']);
    $user = User::query()->create(['name' => 'Macro Expired User']);

    Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'ends_at' => CarbonImmutable::now()->subDay(),
    ]);

    expect(app(PremiumAccess::class)->userHasActive($user))->toBeFalse();
});
