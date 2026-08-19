<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Crops\Models\Crop;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            [
                'name' => ['en' => 'Tomato', 'ru' => 'Томат', 'uz' => 'Pomidor'],
                'description' => ['en' => 'Red fruit vegetable', 'ru' => 'Красный овощ-фрукт', 'uz' => 'Qizil sabzavot-meva'],
            ],
            [
                'name' => ['en' => 'Eggplant', 'ru' => 'Баклажан', 'uz' => 'Baqlajon'],
                'description' => ['en' => 'Purple vegetable', 'ru' => 'Фиолетовый овощ', 'uz' => 'Binafsha sabzavot'],
            ],
            [
                'name' => ['en' => 'Cucumber', 'ru' => 'Огурец', 'uz' => 'Bodring'],
                'description' => ['en' => 'Green vegetable', 'ru' => 'Зеленый овощ', 'uz' => 'Yashil sabzavot'],
            ],
            [
                'name' => ['en' => 'Pepper', 'ru' => 'Перец', 'uz' => 'Qalampir'],
                'description' => ['en' => 'Bell pepper vegetable', 'ru' => 'Болгарский перец', 'uz' => 'Bulg\'or qalampiri'],
            ],
            [
                'name' => ['en' => 'Potato', 'ru' => 'Картофель', 'uz' => 'Kartoshka'],
                'description' => ['en' => 'Root vegetable', 'ru' => 'Корнеплод', 'uz' => 'Ildiz sabzavot'],
            ],
            [
                'name' => ['en' => 'Carrot', 'ru' => 'Морковь', 'uz' => 'Sabzi'],
                'description' => ['en' => 'Orange root vegetable', 'ru' => 'Оранжевый корнеплод', 'uz' => 'To\'q sariq ildiz sabzavot'],
            ],
            [
                'name' => ['en' => 'Onion', 'ru' => 'Лук', 'uz' => 'Piyoz'],
                'description' => ['en' => 'Bulb vegetable', 'ru' => 'Луковичный овощ', 'uz' => 'Piyozli sabzavot'],
            ],
            [
                'name' => ['en' => 'Cabbage', 'ru' => 'Капуста', 'uz' => 'Karam'],
                'description' => ['en' => 'Leafy vegetable', 'ru' => 'Листовой овощ', 'uz' => 'Bargli sabzavot'],
            ],
            [
                'name' => ['en' => 'Watermelon', 'ru' => 'Арбуз', 'uz' => 'Tarvuz'],
                'description' => ['en' => 'Large fruit', 'ru' => 'Большая ягода', 'uz' => 'Katta meva'],
            ],
        ];

        foreach ($crops as $index => $cropData) {
            Crop::updateOrCreate(
                ['name->en' => $cropData['name']['en']],
                [
                    'name' => $cropData['name'],
                    'description' => $cropData['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
