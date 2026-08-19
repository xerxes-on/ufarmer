<?php

declare(strict_types=1);

return [
    'image_already_scanned' => 'This image has already been scanned.',
    'scan_started' => 'Plant scan started successfully.',
    'scan_failed' => 'Failed to start plant scan.',
    'scan_not_found' => 'Scan not found.',
    'ai_analysis_failed' => 'AI analysis failed.',

    // Validation messages
    'validation' => [
        'image_required' => 'Image is required',
        'image_provide' => 'Please provide an image to scan.',
        'image_invalid' => 'File must be an image',
        'image_must_be' => 'The file must be an image.',
        'image_max_size' => 'Image size must not exceed 10MB',
        'image_max_100mb' => 'The image size must not exceed 10MB.',
        'image_invalid_format' => 'Image must be jpeg, png, jpg, gif, or svg',
        'lang_invalid' => 'Language must be one of: en, ru, uz',
        'ai_provider_invalid' => 'Invalid AI provider. Supported providers: openai, gemini, claude.',
    ],
];
