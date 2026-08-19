<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Oʻzgidromet',
    'navigation_label' => 'Ob-havo prognozlari',
    'model_label' => 'Ob-havo prognozi',
    'plural_model_label' => 'Ob-havo prognozlari',

    'form' => [
        'section_title' => 'Yuklash',
        'section_description' => 'Ob-havo prognozi faylini yuklang (PDF yoki DOCX, eng koʻpi 20 MB).',
        'file' => 'Fayl',
        'helper' => 'Ruxsat etilgan: PDF, DOCX. Eng katta hajmi: 20 MB.',
    ],

    'table' => [
        'filename' => 'Fayl nomi',
        'type' => 'Tur',
        'size' => 'Hajmi',
        'uploaded_by' => 'Yuklagan',
        'uploaded_at' => 'Yuklangan vaqti',
    ],

    'actions' => [
        'download' => 'Yuklab olish',
    ],
];
