<?php

declare(strict_types=1);

namespace Ufarm\Premium\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\app\Models\User;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Models\PremiumPlan;
use Ufarm\Premium\Models\Subscription;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $user = User::query()->first() ?? User::query()->create([
            'name' => 'Premium Test User',
        ]);

        $plan = PremiumPlan::query()->first() ?? PremiumPlan::factory()->create();

        return [
            'user_id' => $user->id,
            'premium_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => CarbonImmutable::now()->subWeek(),
            'ends_at' => CarbonImmutable::now()->addMonth(),
            'application_id' => null,
            'metadata' => [],
        ];
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::EXPIRED,
            'ends_at' => CarbonImmutable::now()->subDay(),
        ]);
    }
}
