<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Агрокалькулятор',

    'resources' => [
        'calculator_run' => [
            'navigation' => 'Запуски калькулятора',
            'label' => 'Запуск калькулятора',
            'plural_label' => 'Запуски калькулятора',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Культура',
                    'yield' => 'Урожайность (т/га)',
                    'risk_level' => 'Уровень риска',
                    'ran_at' => 'Дата запуска',
                ],
            ],
        ],

        'scoring_model' => [
            'navigation' => 'Модели оценки',
            'label' => 'Модель оценки',
            'plural_label' => 'Модели оценки',

            'form' => [
                'name' => 'Название',
                'code' => 'Код',
                'scope' => 'Область',
                'version' => 'Версия',
                'is_active' => 'Активна',
                'valid_from' => 'Действует с',
                'valid_to' => 'Действует до',
                'spec' => 'Спецификация JSON',
                'meta' => 'Метаданные JSON',
            ],

            'table' => [
                'columns' => [
                    'name' => 'Название',
                    'code' => 'Код',
                    'version' => 'Версия',
                    'is_active' => 'Активна',
                    'valid_from' => 'Действует с',
                    'valid_to' => 'Действует до',
                ],
            ],
        ],

        'scoring_run' => [
            'navigation' => 'Запуски оценки',
            'label' => 'Запуск оценки',
            'plural_label' => 'Запуски оценки',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Культура',
                    'score' => 'Балл',
                    'grade' => 'Оценка',
                    'created_at' => 'Создано',
                ],
            ],
        ],

        'recommendation_rule' => [
            'navigation' => 'Правила рекомендаций',
            'label' => 'Правило рекомендации',
            'plural_label' => 'Правила рекомендаций',

            'form' => [
                'code' => 'Код',
                'is_active' => 'Активно',
                'title' => 'Переводы заголовка',
                'conditions' => 'Условия JSON',
                'recommendations' => 'Рекомендации JSON',
            ],

            'table' => [
                'columns' => [
                    'code' => 'Код',
                    'title_en' => 'Заголовок (EN)',
                    'is_active' => 'Активно',
                    'updated' => 'Обновлено',
                ],
            ],
        ],

        'scoring_threshold' => [
            'navigation' => 'Пороги оценки',
            'label' => 'Порог оценки',
            'plural_label' => 'Пороги оценки',

            'form' => [
                'scoring_model_id' => 'Модель оценки',
                'metric_key' => 'Ключ метрики',
                'min_value' => 'Мин. значение',
                'max_value' => 'Макс. значение',
                'label' => 'Метка',
                'meta' => 'Метаданные',
            ],

            'table' => [
                'columns' => [
                    'model' => 'Модель',
                    'metric_key' => 'Ключ метрики',
                    'min_value' => 'Мин. значение',
                    'max_value' => 'Макс. значение',
                    'label' => 'Метка',
                ],
            ],
        ],

        'crop_parameter_set' => [
            'navigation' => 'Наборы параметров культур',
            'label' => 'Набор параметров культуры',
            'plural_label' => 'Наборы параметров культур',

            'form' => [
                'crop_id' => 'Культура',
                'version' => 'Версия',
                'is_active' => 'Активен',
                'valid_from' => 'Действует с',
                'valid_to' => 'Действует до',
                'params' => 'Параметры (JSON)',
                'meta' => 'Метаданные (JSON)',
            ],

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Культура',
                    'version' => 'Версия',
                    'is_active' => 'Активен',
                    'valid_from' => 'Действует с',
                    'valid_to' => 'Действует до',
                    'created' => 'Создано',
                ],
            ],
        ],
    ],
];
