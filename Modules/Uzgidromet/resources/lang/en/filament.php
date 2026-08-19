<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Uzgidromet',
    'navigation_label' => 'Weather Prognoses',
    'model_label' => 'Weather Prognosis',
    'plural_model_label' => 'Weather Prognoses',

    'form' => [
        'section_title' => 'Upload',
        'section_description' => 'Upload a weather prognosis file (PDF or DOCX, max 20 MB).',
        'file' => 'File',
        'helper' => 'Allowed: PDF, DOCX. Maximum size: 20 MB.',
    ],

    'table' => [
        'filename' => 'Filename',
        'type' => 'Type',
        'size' => 'Size',
        'uploaded_by' => 'Uploaded by',
        'uploaded_at' => 'Uploaded at',
    ],

    'actions' => [
        'download' => 'Download',
    ],
];
