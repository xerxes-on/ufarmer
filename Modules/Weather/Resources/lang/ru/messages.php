<?php

declare(strict_types=1);

return [
    'validation' => [
        'latitude_required' => 'Широта обязательна.',
        'latitude_numeric' => 'Широта должна быть числом.',
        'latitude_between' => 'Широта должна быть между -90 и 90.',
        'longitude_required' => 'Долгота обязательна.',
        'longitude_numeric' => 'Долгота должна быть числом.',
        'longitude_between' => 'Долгота должна быть между -180 и 180.',
        'crop_id_required' => 'ID культуры обязателен.',
        'crop_id_exists' => 'Выбранная культура не существует.',
        'crop_uuid_required' => 'ID культуры обязателен.',
        'crop_uuid_exists' => 'Выбранная культура не существует.',
    ],
];
