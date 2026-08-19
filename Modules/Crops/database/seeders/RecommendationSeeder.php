<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Crops\Models\Recommendation;

class RecommendationSeeder extends Seeder
{
    public function run(): void
    {
        $recommendations = [
            [
                'recommendation' => [
                    'en' => 'Increase organic matter in soil',
                    'ru' => 'Увеличить органическое вещество в почве',
                    'uz' => 'Tuproqdagi organik moddani oshirish',
                ],
                'justification' => [
                    'en' => 'Low organic matter content detected',
                    'ru' => 'Обнаружено низкое содержание органического вещества',
                    'uz' => 'Organik moddaning past miqdori aniqlandi',
                ],
                'is_active' => true,
            ],
            [
                'recommendation' => [
                    'en' => 'Adjust soil pH levels',
                    'ru' => 'Скорректировать уровень pH почвы',
                    'uz' => 'Tuproq pH darajasini to\'g\'rilash',
                ],
                'justification' => [
                    'en' => 'pH is outside optimal range for crops',
                    'ru' => 'pH находится за пределами оптимального диапазона для культур',
                    'uz' => 'pH ekinlar uchun optimal diapazondan tashqarida',
                ],
                'is_active' => true,
            ],
            [
                'recommendation' => [
                    'en' => 'Apply nitrogen fertilizer',
                    'ru' => 'Применить азотное удобрение',
                    'uz' => 'Azot o\'g\'itini qo\'llash',
                ],
                'justification' => [
                    'en' => 'Nitrogen deficiency detected in soil test',
                    'ru' => 'Обнаружен дефицит азота при анализе почвы',
                    'uz' => 'Tuproq tahlilida azot tanqisligi aniqlandi',
                ],
                'is_active' => true,
            ],
            [
                'recommendation' => [
                    'en' => 'Improve soil drainage',
                    'ru' => 'Улучшить дренаж почвы',
                    'uz' => 'Tuproq drenajini yaxshilash',
                ],
                'justification' => [
                    'en' => 'Signs of waterlogging observed',
                    'ru' => 'Обнаружены признаки переувлажнения',
                    'uz' => 'Suvning ortiqcha to\'planishi belgilari kuzatildi',
                ],
                'is_active' => true,
            ],
            [
                'recommendation' => [
                    'en' => 'Implement crop rotation',
                    'ru' => 'Внедрить севооборот',
                    'uz' => 'Ekin almashtirishni joriy qilish',
                ],
                'justification' => [
                    'en' => 'Same crop planted for multiple seasons',
                    'ru' => 'Одна и та же культура выращивалась несколько сезонов',
                    'uz' => 'Bir xil ekin bir necha mavsum ekilgan',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($recommendations as $data) {
            Recommendation::create($data);
        }
    }
}
