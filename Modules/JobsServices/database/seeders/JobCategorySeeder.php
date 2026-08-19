<?php

declare(strict_types=1);

namespace Modules\JobsServices\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\JobsServices\Models\JobCategory;

class JobCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Обработка земли',
                'slug' => 'land-processing',
                'icon' => '🚜',
                'order' => 1,
            ],
            [
                'name' => 'Посев',
                'slug' => 'sowing',
                'icon' => '🌱',
                'order' => 2,
            ],
            [
                'name' => 'Уборка урожая',
                'slug' => 'harvesting',
                'icon' => '🌾',
                'order' => 3,
            ],
            [
                'name' => 'Орошение',
                'slug' => 'irrigation',
                'icon' => '💧',
                'order' => 4,
            ],
            [
                'name' => 'Удобрение',
                'slug' => 'fertilization',
                'icon' => '🌿',
                'order' => 5,
            ],
            [
                'name' => 'Защита растений',
                'slug' => 'plant-protection',
                'icon' => '🛡️',
                'order' => 6,
            ],
            [
                'name' => 'Транспортные услуги',
                'slug' => 'transport',
                'icon' => '🚚',
                'order' => 7,
            ],
            [
                'name' => 'Садоводство',
                'slug' => 'gardening',
                'icon' => '🌳',
                'order' => 8,
            ],
            [
                'name' => 'Животноводство',
                'slug' => 'livestock',
                'icon' => '🐄',
                'order' => 9,
            ],
            [
                'name' => 'Другое',
                'slug' => 'other',
                'icon' => '📋',
                'order' => 10,
            ],
        ];

        foreach ($categories as $category) {
            JobCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
