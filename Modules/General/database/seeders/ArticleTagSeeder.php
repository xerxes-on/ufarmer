<?php

declare(strict_types=1);

namespace Modules\General\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\General\Models\ArticleTag;

class ArticleTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Core actions (3 languages)
            ['slug' => 'irrigation', 'name_uz' => 'Sugʻorish', 'name_ru' => 'Орошение', 'name_oz' => 'Irrigation'],
            ['slug' => 'fertilization', 'name_uz' => 'Oʻgʻitlash', 'name_ru' => 'Подкормка удобрениями', 'name_oz' => 'Fertilization'],
            ['slug' => 'disease-protection', 'name_uz' => 'Kasalliklardan himoya', 'name_ru' => 'Защита от болезней', 'name_oz' => 'Disease Protection'],
            ['slug' => 'pest-protection', 'name_uz' => 'Zararkunandalardan himoya', 'name_ru' => 'Защита от вредителей', 'name_oz' => 'Pest Protection'],
            ['slug' => 'harvest', 'name_uz' => 'Hosil yigʻimi', 'name_ru' => 'Уборка урожая', 'name_oz' => 'Harvest'],
            ['slug' => 'weeding', 'name_uz' => 'Begona oʻtlarni yoʻqotish', 'name_ru' => 'Прополка', 'name_oz' => 'Weeding'],
            ['slug' => 'tying', 'name_uz' => 'Bogʻlash', 'name_ru' => 'Подвязка', 'name_oz' => 'Trellising'],
            ['slug' => 'pollination', 'name_uz' => 'Changlatish', 'name_ru' => 'Опыление', 'name_oz' => 'Pollination'],
            ['slug' => 'pruning-training', 'name_uz' => 'Azizillo va shakllantirish', 'name_ru' => 'Обрезка и формировка', 'name_oz' => 'Pruning and Training'],
            ['slug' => 'orchard-treatment', 'name_uz' => 'Bogʻga ishlov berish', 'name_ru' => 'Обработка сада', 'name_oz' => 'Orchard Treatment'],

            // Management themes
            ['slug' => 'soil-management', 'name_uz' => 'Tuproqni boshqarish', 'name_ru' => 'Управление почвой', 'name_oz' => 'Soil Management'],
            ['slug' => 'nursery-management', 'name_uz' => 'Koʻchatxonani boshqarish', 'name_ru' => 'Управление рассадником', 'name_oz' => 'Nursery Management'],
            ['slug' => 'crop-rotation', 'name_uz' => 'Ekin almashinuvi', 'name_ru' => 'Севооборот', 'name_oz' => 'Crop Rotation'],
            ['slug' => 'stand-establishment', 'name_uz' => 'Koʻchat chiqishini taʼminlash', 'name_ru' => 'Получение всходов', 'name_oz' => 'Stand Establishment'],
            ['slug' => 'postharvest', 'name_uz' => 'Hosildan keyingi ishlov', 'name_ru' => 'Послеуборочная обработка', 'name_oz' => 'Postharvest'],
            ['slug' => 'greenhouse-management', 'name_uz' => 'Issiqxona boshqaruvi', 'name_ru' => 'Тепличное хозяйство', 'name_oz' => 'Greenhouse Management'],
            ['slug' => 'orchard-management', 'name_uz' => 'Bogʻni boshqarish', 'name_ru' => 'Управление садом', 'name_oz' => 'Orchard Management'],
            ['slug' => 'biologicals', 'name_uz' => 'Biopreparatlar', 'name_ru' => 'Биопрепараты', 'name_oz' => 'Biologicals'],
            ['slug' => 'plant-protection', 'name_uz' => 'Oʻsimliklarni himoya qilish', 'name_ru' => 'Защита растений', 'name_oz' => 'Plant Protection'],

            // Existing slugs used in articles
            ['slug' => 'nutrient-management', 'name_uz' => 'Oziqlanishni boshqarish', 'name_ru' => 'Управление питанием', 'name_oz' => 'Nutrient Management'],
            ['slug' => 'canopy-management', 'name_uz' => 'Kronani boshqarish', 'name_ru' => 'Управление кроной', 'name_oz' => 'Canopy Management'],
            ['slug' => 'pest-management', 'name_uz' => 'Zararkunandalarga qarshi kurash', 'name_ru' => 'Защита от вредителей', 'name_oz' => 'Pest Management'],
            ['slug' => 'disease-management', 'name_uz' => 'Kasalliklarga qarshi kurash', 'name_ru' => 'Защита от болезней', 'name_oz' => 'Disease Management'],
            ['slug' => 'integrated-pest-management', 'name_uz' => 'Integratsiyalashgan himoya', 'name_ru' => 'Интегрированная защита растений', 'name_oz' => 'Integrated Pest Management'],
        ];

        foreach ($tags as $t) {
            ArticleTag::updateOrCreate(
                ['slug' => $t['slug']],
                [
                    'name_uz' => $t['name_uz'],
                    'name_ru' => $t['name_ru'],
                    'name_oz' => $t['name_oz'],
                ]
            );
        }
    }
}
