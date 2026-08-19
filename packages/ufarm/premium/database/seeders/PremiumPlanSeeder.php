<?php

declare(strict_types=1);

namespace Ufarm\Premium\Database\Seeders;

use Illuminate\Database\Seeder;
use Ufarm\Premium\Models\Currency;
use Ufarm\Premium\Models\PremiumPlan;
use Xerxes\AuthBridge\Models\Application;

class PremiumPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Get application IDs
        $erpApp = Application::where('alias', 'erp')->first();
        $farmerApp = Application::where('alias', 'farmer')->first();
        $marketplaceApp = Application::where('alias', 'marketplace')->first();

        // Get USD currency
        $usdCurrency = Currency::where('code', 'USD')->first();

        $plans = [
            // Universal plans (available for all apps)
            [
                'name' => 'Starter',
                'price' => 9.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 30,
                'application_id' => null,
            ],
            [
                'name' => 'Growth',
                'price' => 24.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 90,
                'application_id' => null,
            ],
            [
                'name' => 'Enterprise',
                'price' => 79.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 365,
                'application_id' => null,
            ],

            // ERP-specific plans
            [
                'name' => 'ERP Basic',
                'price' => 49.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 30,
                'application_id' => $erpApp?->id,
            ],
            [
                'name' => 'ERP Professional',
                'price' => 99.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 30,
                'application_id' => $erpApp?->id,
            ],

            // Farmer App specific plans
            [
                'name' => 'Farmer Premium',
                'price' => 14.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 30,
                'application_id' => $farmerApp?->id,
            ],

            // Marketplace specific plans
            [
                'name' => 'Marketplace Seller',
                'price' => 19.99,
                'currency_id' => $usdCurrency->id,
                'duration_days' => 30,
                'application_id' => $marketplaceApp?->id,
            ],
        ];

        foreach ($plans as $plan) {
            // Skip if application-specific and app doesn't exist
            if (isset($plan['application_id']) && $plan['application_id'] === null) {
                continue;
            }

            PremiumPlan::query()->updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
