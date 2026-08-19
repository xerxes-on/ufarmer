<?php

declare(strict_types=1);

namespace Ufarm\Premium\Console\Commands;

use Illuminate\Console\Command;
use Ufarm\Premium\Database\Seeders\PremiumPlanSeeder;

class SeedPremiumPlansCommand extends Command
{
    protected $signature = 'premium:seed';

    protected $description = 'Seed the default premium plans.';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => PremiumPlanSeeder::class]);

        $this->components->info('Premium plans seeded.');

        return self::SUCCESS;
    }
}
