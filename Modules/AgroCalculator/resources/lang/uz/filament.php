<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Agro Kalkulyator',
    'locales' => [
        'en' => 'Inglizcha',
        'ru' => 'Ruscha',
        'uz' => 'O‘zbekcha',
    ],
    'pages' => [
        'run_calculator' => [
            'navigation' => 'Agro kalkulyator',
            'title' => 'Agro kalkulyator',
            'description' => 'Agronomik hisob-kitoblarni ishga tushiring va parametr to‘plamlarini ko‘ring.',
            'sections' => [
                'configuration' => 'Sozlamalar',
                'inputs' => 'Kiritmalar',
                'results' => 'Natijalar',
                'parameters' => 'Parametrlar sharhi',
            ],
            'fields' => [
                'crop' => 'Ekin',
                'area_crop' => 'Ekin maydoni',
            ],
            'helper' => [
                'default' => 'Standart: :value',
                'options' => 'Variantlar: :list',
                'factors' => 'Faktorlar: :list',
            ],
            'factor_summaries' => [
                'range' => 'Oraliq :min – :max',
                'optimal' => 'Optimal: :value',
                'threshold' => 'Chegara: :value',
                'target' => 'Maqsad: :value',
                'breakpoints' => ':count ta chegaralar',
                'pressure' => 'Hisobot yoki iqlim bosimi',
            ],
            'summary' => [
                'version' => 'Versiya',
                'valid_from' => 'Boshlanish sanasi',
                'valid_to' => 'Yakun sanasi',
                'baseline_yield' => 'Bazaviy hosildorlik (t/ga)',
                'cycle_days' => 'Sikl (kun)',
                'defaults' => 'Standartlar',
                'weights' => 'Og‘irliklar',
                'factors' => 'Faktorlar',
                'factor' => 'Faktor',
                'weight' => 'Og‘irlik',
                'type' => 'Turi',
                'config' => 'Konfiguratsiya',
            ],
            'results' => [
                'potential_yield' => 'Potensial hosildorlik (t/ga)',
                'stress_index' => 'Stress indeksi',
                'risk_level' => 'Xavf darajasi',
                'score' => 'Ball',
                'grade' => 'Daraja',
                'factors' => 'Faktorlar',
                'value' => 'Qiymat',
                'context' => 'Kontekst',
                'metrics' => 'Ko‘rsatkichlar',
                'flags' => 'Signallar',
                'recommendations' => 'Tavsiyalar',
                'no_recommendations' => 'Tavsiyalar mavjud emas.',
            ],
            'labels' => [
                'unknown_crop' => 'Noma’lum ekin',
                'unknown_area' => 'Noma’lum maydon',
            ],
            'actions' => [
                'calculate' => 'Hisoblashni ishga tushirish',
            ],
            'notifications' => [
                'validation' => [
                    'title' => 'Tekshiruv xatosi',
                    'crop' => 'Hisoblashdan oldin ekinni tanlang.',
                    'area_crop' => 'Hisoblash uchun ekin maydonini tanlang.',
                ],
                'no_parameters' => [
                    'title' => 'Parametrlar topilmadi',
                    'body' => 'Tanlangan ekin uchun faol parametr to‘plami topilmadi.',
                ],
                'tables_missing' => 'Agro kalkulyator jadvallari mavjud emas. Avval modul migratsiyalarini ishga tushiring.',
                'calculation_success' => [
                    'title' => 'Hisoblash yakunlandi',
                    'body' => 'Natijalar quyida ko‘rsatilgan.',
                ],
                'calculation_failed' => [
                    'title' => 'Hisoblash amalga oshmadi',
                ],
            ],
        ],
    ],
    'resources' => [
        'calculator_run' => [
            'navigation' => 'Kalkulyator hisoblari',
            'label' => 'Kalkulyator hisobi',
            'plural_label' => 'Kalkulyator hisoblari',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Ekin',
                    'yield' => 'Potensial hosildorlik (t/ga)',
                    'risk_level' => 'Xavf darajasi',
                    'ran_at' => 'Hisoblangan sana',
                ],
            ],
        ],
        'scoring_run' => [
            'navigation' => 'Baholash hisoblari',
            'label' => 'Baholash hisobi',
            'plural_label' => 'Baholash hisoblari',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Ekin',
                    'score' => 'Ball',
                    'grade' => 'Daraja',
                    'created_at' => 'Yaratilgan',
                ],
            ],
        ],
        'scoring_model' => [
            'navigation' => 'Baholash modellari',
            'label' => 'Baholash modeli',
            'plural_label' => 'Baholash modellari',
            'form' => [
                'name' => 'Nomi',
                'code' => 'Kod',
                'scope' => 'Qamrov',
                'version' => 'Versiya',
                'is_active' => 'Faol',
                'valid_from' => 'Dan amal qiladi',
                'valid_to' => 'Gacha amal qiladi',
                'spec' => 'Spetsifikatsiya',
                'meta' => 'Metadata',
            ],
            'table' => [
                'columns' => [
                    'name' => 'Nomi',
                    'code' => 'Kod',
                    'version' => 'Versiya',
                    'is_active' => 'Faol',
                    'valid_from' => 'Dan amal qiladi',
                    'valid_to' => 'Gacha amal qiladi',
                ],
            ],
        ],
        'scoring_threshold' => [
            'navigation' => 'Baholash chegaralari',
            'label' => 'Baholash chegarasi',
            'plural_label' => 'Baholash chegaralari',
            'form' => [
                'scoring_model_id' => 'Baholash modeli',
                'metric_key' => 'Metrika kaliti',
                'min_value' => 'Min qiymat',
                'max_value' => 'Maks qiymat',
                'label' => 'Belgi',
                'meta' => 'Metadata',
            ],
            'table' => [
                'columns' => [
                    'model' => 'Model',
                    'metric_key' => 'Metrika kaliti',
                    'min_value' => 'Min qiymat',
                    'max_value' => 'Maks qiymat',
                    'label' => 'Belgi',
                ],
            ],
        ],
        'recommendation_rule' => [
            'navigation' => 'Tavsiya qoidalari',
            'label' => 'Tavsiya qoidasi',
            'plural_label' => 'Tavsiya qoidalari',
            'form' => [
                'code' => 'Kod',
                'is_active' => 'Faol',
                'title' => 'Sarlavha',
                'conditions' => 'Shartlar',
                'recommendations' => 'Tavsiyalar',
            ],
            'table' => [
                'columns' => [
                    'code' => 'Kod',
                    'title_en' => 'Sarlavha (EN)',
                    'is_active' => 'Faol',
                    'updated' => 'Yangilangan',
                ],
            ],
        ],
        'crop_parameter_set' => [
            'navigation' => 'Ekin parametrlari to\'plamlari',
            'label' => 'Ekin parametrlari to\'plami',
            'plural_label' => 'Ekin parametrlari to\'plamlari',
            'form' => [
                'crop_id' => 'Ekin',
                'version' => 'Versiya',
                'is_active' => 'Faol',
                'valid_from' => 'Dan amal qiladi',
                'valid_to' => 'Gacha amal qiladi',
                'params' => 'Parametrlar',
                'meta' => 'Metadata',
            ],
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Ekin',
                    'version' => 'Versiya',
                    'is_active' => 'Faol',
                    'valid_from' => 'Dan amal qiladi',
                    'valid_to' => 'Gacha amal qiladi',
                    'created' => 'Yaratilgan',
                ],
            ],
        ],
    ],
];
