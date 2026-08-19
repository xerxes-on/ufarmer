<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Агро Калькулятор',
    'locales' => [
        'en' => 'Английский',
        'ru' => 'Русский',
        'uz' => 'Узбекский',
    ],
    'pages' => [
        'run_calculator' => [
            'navigation' => 'Агро калькулятор',
            'title' => 'Агро калькулятор',
            'description' => 'Запускайте агрономические расчеты и изучайте наборы параметров.',
            'sections' => [
                'configuration' => 'Настройки',
                'inputs' => 'Входные данные',
                'results' => 'Результаты',
                'parameters' => 'Обзор параметров',
            ],
            'fields' => [
                'crop' => 'Культура',
                'area_crop' => 'Посев',
            ],
            'helper' => [
                'default' => 'По умолчанию: :value',
                'options' => 'Варианты: :list',
                'factors' => 'Факторы: :list',
            ],
            'factor_summaries' => [
                'range' => 'Диапазон :min – :max',
                'optimal' => 'Оптимум :value',
                'threshold' => 'Порог :value',
                'target' => 'Цель :value',
                'breakpoints' => ':count точек перелома',
                'pressure' => 'Отчетное или климатическое давление',
            ],
            'summary' => [
                'version' => 'Версия',
                'valid_from' => 'Действует с',
                'valid_to' => 'Действует до',
                'baseline_yield' => 'Базовая урожайность (т/га)',
                'cycle_days' => 'Цикл (дней)',
                'defaults' => 'Значения по умолчанию',
                'weights' => 'Веса',
                'factors' => 'Факторы',
                'factor' => 'Фактор',
                'weight' => 'Вес',
                'type' => 'Тип',
                'config' => 'Конфигурация',
            ],
            'results' => [
                'potential_yield' => 'Потенциальная урожайность (т/га)',
                'stress_index' => 'Индекс стресса',
                'risk_level' => 'Уровень риска',
                'score' => 'Оценка',
                'grade' => 'Класс',
                'factors' => 'Факторы',
                'value' => 'Значение',
                'context' => 'Контекст',
                'metrics' => 'Метрики',
                'flags' => 'Флаги',
                'recommendations' => 'Рекомендации',
                'no_recommendations' => 'Рекомендации не найдены.',
            ],
            'labels' => [
                'unknown_crop' => 'Неизвестная культура',
                'unknown_area' => 'Неизвестный участок',
            ],
            'actions' => [
                'calculate' => 'Запустить расчет',
            ],
            'notifications' => [
                'validation' => [
                    'title' => 'Ошибка валидации',
                    'crop' => 'Выберите культуру перед запуском расчета.',
                    'area_crop' => 'Выберите посев для расчета.',
                ],
                'no_parameters' => [
                    'title' => 'Параметры не найдены',
                    'body' => 'Для выбранной культуры не найден активный набор параметров.',
                ],
                'tables_missing' => 'Таблицы агро калькулятора отсутствуют. Запустите миграции модуля.',
                'calculation_success' => [
                    'title' => 'Расчет выполнен',
                    'body' => 'Результаты показаны ниже.',
                ],
                'calculation_failed' => [
                    'title' => 'Не удалось выполнить расчет',
                ],
            ],
        ],
    ],
    'resources' => [
        'calculator_run' => [
            'navigation' => 'Расчеты калькулятора',
            'label' => 'Расчет калькулятора',
            'plural_label' => 'Расчеты калькулятора',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Культура',
                    'yield' => 'Потенциальная урожайность (т/га)',
                    'risk_level' => 'Уровень риска',
                    'ran_at' => 'Дата расчета',
                ],
            ],
        ],
        'scoring_run' => [
            'navigation' => 'Расчеты оценок',
            'label' => 'Расчет оценки',
            'plural_label' => 'Расчеты оценок',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Культура',
                    'score' => 'Оценка',
                    'grade' => 'Класс',
                    'created_at' => 'Создано',
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
                'valid_from' => 'Действительна с',
                'valid_to' => 'Действительна до',
                'spec' => 'Спецификация',
                'meta' => 'Метаданные',
            ],
            'table' => [
                'columns' => [
                    'name' => 'Название',
                    'code' => 'Код',
                    'version' => 'Версия',
                    'is_active' => 'Активна',
                    'valid_from' => 'Действительна с',
                    'valid_to' => 'Действительна до',
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
        'recommendation_rule' => [
            'navigation' => 'Правила рекомендаций',
            'label' => 'Правило рекомендации',
            'plural_label' => 'Правила рекомендаций',
            'form' => [
                'code' => 'Код',
                'is_active' => 'Активно',
                'title' => 'Заголовок',
                'conditions' => 'Условия',
                'recommendations' => 'Рекомендации',
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
        'crop_parameter_set' => [
            'navigation' => 'Наборы параметров культур',
            'label' => 'Набор параметров культуры',
            'plural_label' => 'Наборы параметров культур',
            'form' => [
                'crop_id' => 'Культура',
                'version' => 'Версия',
                'is_active' => 'Активен',
                'valid_from' => 'Действителен с',
                'valid_to' => 'Действителен до',
                'params' => 'Параметры',
                'meta' => 'Метаданные',
            ],
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Культура',
                    'version' => 'Версия',
                    'is_active' => 'Активен',
                    'valid_from' => 'Действителен с',
                    'valid_to' => 'Действителен до',
                    'created' => 'Создан',
                ],
            ],
        ],
    ],
];
