<?php

declare(strict_types=1);

use Ufarm\Premium\Filament\Resources\PremiumPlanResource;
use Ufarm\Premium\Models\PremiumPlan;

it('binds the premium plan model to the filament resource', function (): void {
    expect(PremiumPlanResource::getModel())
        ->toBe(PremiumPlan::class);
});

it('defines list, create and edit pages', function (): void {
    $pages = PremiumPlanResource::getPages();

    expect($pages)
        ->toHaveKey('index')
        ->and($pages)->toHaveKey('create')
        ->and($pages)->toHaveKey('edit');
});
