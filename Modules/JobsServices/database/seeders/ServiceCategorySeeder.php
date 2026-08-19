<?php

declare(strict_types=1);

namespace Modules\JobsServices\database\seeders;

use Illuminate\Database\Seeder;
use Modules\JobsServices\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Услуги трактора',
                'slug' => 'tractor-services',
                'icon' => '🚜',
                'description' => 'Вспашка, культивация, другие работы с трактором',
                'sort_order' => 1,
            ],
            [
                'name' => 'Услуги комбайна',
                'slug' => 'combine-services',
                'icon' => '🌾',
                'description' => 'Уборка зерновых и других культур',
                'sort_order' => 2,
            ],
            [
                'name' => 'Транспортировка',
                'slug' => 'transportation',
                'icon' => '🚚',
                'description' => 'Перевозка урожая, материалов, техники',
                'sort_order' => 3,
            ],
            [
                'name' => 'Опрыскивание',
                'slug' => 'spraying',
                'icon' => '💦',
                'description' => 'Обработка полей от вредителей',
                'sort_order' => 4,
            ],
            [
                'name' => 'Посевные работы',
                'slug' => 'sowing-work',
                'icon' => '🌱',
                'description' => 'Услуги по посеву различных культур',
                'sort_order' => 5,
            ],
            [
                'name' => 'Ирригация',
                'slug' => 'irrigation-service',
                'icon' => '💧',
                'description' => 'Установка и обслуживание систем полива',
                'sort_order' => 6,
            ],
            [
                'name' => 'Консультации',
                'slug' => 'consulting',
                'icon' => '👨‍🌾',
                'description' => 'Агрономические консультации',
                'sort_order' => 7,
            ],
            [
                'name' => 'Ремонт техники',
                'slug' => 'equipment-repair',
                'icon' => '🔧',
                'description' => 'Ремонт сельхозтехники',
                'sort_order' => 8,
            ],
            [
                'name' => 'Сезонные работы',
                'slug' => 'seasonal-work',
                'icon' => '📅',
                'description' => 'Помощь в сезонных работах',
                'sort_order' => 9,
            ],
            [
                'name' => 'Другие услуги',
                'slug' => 'other-services',
                'icon' => '📋',
                'description' => 'Прочие сельскохозяйственные услуги',
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::create($category);
        }
    }
}
