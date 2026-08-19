<?php

declare(strict_types=1);

namespace Modules\JobsServices\Database\Seeders;

use Illuminate\Database\Seeder;

class JobsServicesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\JobsServices\database\seeders\JobCategorySeeder::class,
            \Modules\JobsServices\database\seeders\ServiceCategorySeeder::class,
        ]);
    }
}
