<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Ufarm\Premium\Models\PremiumPlan;

it('seeds default premium plans', function (): void {
    Artisan::call('premium:seed');

    expect(PremiumPlan::query()->count())->toBeGreaterThanOrEqual(3)
        ->and(PremiumPlan::query()->whereIn('name', ['Starter', 'Growth', 'Enterprise'])->count())
        ->toBe(3);
});
