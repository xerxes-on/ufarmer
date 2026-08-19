<?php

declare(strict_types=1);

namespace Ufarm\Premium\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ufarm\Premium\Models\Currency;
use Ufarm\Premium\Models\PremiumPlan;

class PremiumPlanFactory extends Factory
{
    protected $model = PremiumPlan::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true).' Plan';

        // Ensure currency exists, create if not
        $currency = Currency::where('code', 'USD')->first();
        if (! $currency) {
            $currency = Currency::create([
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_active' => true,
            ]);
        }

        return [
            'name' => $name,
            'price' => $this->faker->randomFloat(2, 9, 199),
            'currency_id' => $currency->id,
            'duration_days' => $this->faker->randomElement([30, 90, 365]),
            'is_active' => true,
        ];
    }
}
