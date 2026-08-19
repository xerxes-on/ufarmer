<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Узгидромет',
    'navigation_label' => 'Прогнозы погоды',
    'model_label' => 'Прогноз погоды',
    'plural_model_label' => 'Прогнозы погоды',

    'form' => [
        'section_title' => 'Загрузка',
        'section_description' => 'Загрузите файл прогноза погоды (PDF или DOCX, до 20 МБ).',
        'file' => 'Файл',
        'helper' => 'Разрешены: PDF, DOCX. Максимальный размер: 20 МБ.',
    ],

    'table' => [
        'filename' => 'Имя файла',
        'type' => 'Тип',
        'size' => 'Размер',
        'uploaded_by' => 'Загрузил',
        'uploaded_at' => 'Загружено',
    ],

    'actions' => [
        'download' => 'Скачать',
    ],
];
