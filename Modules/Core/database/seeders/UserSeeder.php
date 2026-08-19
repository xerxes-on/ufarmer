<?php

declare(strict_types=1);

namespace Modules\Core\database\seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Modules\Core\Models\User::query()->create([
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@xerxes.io',
            'lang' => 'en',
            'password' => bcrypt('password'),
            'phone' => '998331828251',
        ]);
    }
}
