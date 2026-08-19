<?php

declare(strict_types=1);

return [
    'image_already_scanned' => 'Это изображение уже было отсканировано.',
    'scan_started' => 'Сканирование растения успешно начато.',
    'scan_failed' => 'Не удалось начать сканирование растения.',
    'scan_not_found' => 'Сканирование не найдено.',
    'ai_analysis_failed' => 'Не удалось выполнить анализ AI.',

    // Validation messages
    'validation' => [
        'image_required' => 'Требуется изображение',
        'image_provide' => 'Пожалуйста, предоставьте изображение для сканирования.',
        'image_invalid' => 'Файл должен быть изображением',
        'image_must_be' => 'Файл должен быть изображением.',
        'image_max_size' => 'Размер изображения не должен превышать 10 МБ',
        'image_max_100mb' => 'Размер изображения не должен превышать 10 МБ.',
        'image_invalid_format' => 'Изображение должно быть в формате jpeg, png, jpg, gif или svg',
        'lang_invalid' => 'Язык должен быть одним из: en, ru, uz',
        'ai_provider_invalid' => 'Неверный AI провайдер. Поддерживаемые провайдеры: openai, gemini, claude.',
    ],
];
