<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Modules\Core\app\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Middleware\EnsurePremiumSubscription;
use Ufarm\Premium\Models\PremiumPlan;
use Ufarm\Premium\Models\Subscription;

it('denies access when user lacks subscription', function (): void {
    $user = User::query()->create(['name' => 'Missing Premium']);

    $request = Request::create('/premium', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsurePremiumSubscription;

    expect(fn () => $middleware->handle($request, fn () => response('ok'), 'plan:Starter'))
        ->toThrow(HttpException::class);
});

it('allows access when subscription exists via pivot', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Starter']);
    $user = User::query()->create(['name' => 'Premium User']);

    Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
    ]);

    $request = Request::create('/premium', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsurePremiumSubscription;

    $response = $middleware->handle($request, fn () => response('ok'), 'plan:Starter');

    expect($response->getContent())->toBe('ok');
});

it('honors application parameter on pivot check', function (): void {
    $plan = PremiumPlan::factory()->create(['name' => 'Growth']);
    $user = User::query()->create(['name' => 'ERP Premium User']);

    Subscription::query()->create([
        'user_id' => $user->id,
        'premium_plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'application_id' => 1,
    ]);

    $request = Request::create('/premium', 'GET');
    $request->headers->set('X-Application', 'erp');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsurePremiumSubscription;

    $response = $middleware->handle($request, fn () => response('ok'), 'plan:Growth');

    expect($response->getContent())->toBe('ok');
});
