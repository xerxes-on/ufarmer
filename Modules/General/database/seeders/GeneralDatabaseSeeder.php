<?php

declare(strict_types=1);

namespace Modules\General\Database\Seeders;

use Illuminate\Database\Seeder;

class GeneralDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            //            StorySeeder::class,
            ArticleTagSeeder::class,
            ArticleSeeder::class,
            ProductStatSeeder::class,
        ]);
    }
}
