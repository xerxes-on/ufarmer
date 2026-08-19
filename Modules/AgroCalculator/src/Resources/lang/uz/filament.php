<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Agro Kalkulyator',

    'resources' => [
        'calculator_run' => [
            'navigation' => 'Kalkulyator ishga tushirishlari',
            'label' => 'Kalkulyator ishga tushirish',
            'plural_label' => 'Kalkulyator ishga tushirishlari',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Ekin',
                    'yield' => 'Hosildorlik (t/ga)',
                    'risk_level' => 'Xavf darajasi',
                    'ran_at' => 'Ishga tushirilgan vaqt',
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
                'valid_from' => 'Amal qilish boshlanishi',
                'valid_to' => 'Amal qilish tugashi',
                'spec' => 'Spetsifikatsiya JSON',
                'meta' => 'Metama\'lumotlar JSON',
            ],

            'table' => [
                'columns' => [
                    'name' => 'Nomi',
                    'code' => 'Kod',
                    'version' => 'Versiya',
                    'is_active' => 'Faol',
                    'valid_from' => 'Boshlanishi',
                    'valid_to' => 'Tugashi',
                ],
            ],
        ],

        'scoring_run' => [
            'navigation' => 'Baholash ishga tushirishlari',
            'label' => 'Baholash ishga tushirish',
            'plural_label' => 'Baholash ishga tushirishlari',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Ekin',
                    'score' => 'Ball',
                    'grade' => 'Baho',
                    'created_at' => 'Yaratilgan',
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
                'title' => 'Sarlavha tarjimalari',
                'conditions' => 'Shartlar JSON',
                'recommendations' => 'Tavsiyalar JSON',
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

        'scoring_threshold' => [
            'navigation' => 'Baholash chegaralari',
            'label' => 'Baholash chegarasi',
            'plural_label' => 'Baholash chegaralari',

            'form' => [
                'scoring_model_id' => 'Baholash modeli',
                'metric_key' => 'Metrika kaliti',
                'min_value' => 'Min. qiymat',
                'max_value' => 'Maks. qiymat',
                'label' => 'Belgi',
                'meta' => 'Metama\'lumotlar',
            ],

            'table' => [
                'columns' => [
                    'model' => 'Model',
                    'metric_key' => 'Metrika kaliti',
                    'min_value' => 'Min. qiymat',
                    'max_value' => 'Maks. qiymat',
                    'label' => 'Belgi',
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
                'valid_from' => 'Amal qilish boshlanishi',
                'valid_to' => 'Amal qilish tugashi',
                'params' => 'Parametrlar (JSON)',
                'meta' => 'Metama\'lumotlar (JSON)',
            ],

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Ekin',
                    'version' => 'Versiya',
                    'is_active' => 'Faol',
                    'valid_from' => 'Boshlanishi',
                    'valid_to' => 'Tugashi',
                    'created' => 'Yaratilgan',
                ],
            ],
        ],
    ],
];
