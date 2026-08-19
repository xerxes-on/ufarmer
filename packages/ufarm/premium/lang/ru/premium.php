<?php

return [
    'navigation' => [
        'group' => 'Премиум',
        'label' => 'Премиум Планы',
    ],

    'model' => [
        'label' => 'Премиум План',
        'plural' => 'Премиум Планы',
    ],

    'fields' => [
        'name' => 'Название',
        'currency' => 'Валюта',
        'price' => 'Цена',
        'duration_days' => 'Длительность (дни)',
        'application' => 'Приложение',
        'active' => 'Активный',
        'application_helper' => 'Оставьте пустым для универсальных планов, доступных всем приложениям',
        'updated' => 'Обновлено',
        'subscribers' => 'Подписчики',
        'duration' => 'Длительность',
        'universal' => 'Универсальный',
    ],

    'tabs' => [
        'all' => 'Все Планы',
        'universal' => 'Универсальные',
    ],

    'filters' => [
        'active' => 'Активные',
        'application' => 'Приложение',
        'universal' => 'Универсальный',
    ],

    'actions' => [
        'create' => 'Создать Премиум План',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
    ],
];
