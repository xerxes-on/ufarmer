<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Knowledge Base',
        'articles' => 'Articles',
    ],
    'locales' => [
        'uz' => 'Uzbek',
        'oz' => 'Uzbek (Latin)',
        'ru' => 'Russian',
    ],
    'resources' => [
        'article' => [
            'label' => 'Article',
            'plural_label' => 'Articles',
            'tabs' => [
                'content' => 'Article content',
            ],
            'sections' => [
                'media' => 'Media & metadata',
                'crops' => 'Related crops',
            ],
            'fields' => [
                'title' => 'Title',
                'preview' => 'Preview',
                'body' => 'Body',
                'preview_image' => 'Preview image',
                'icon' => 'Icon',
                'attachment' => 'Attachment',
                'crops' => 'Crops',
            ],
            'placeholders' => [
                'crops' => 'Select crops related to this article',
            ],
            'filters' => [
                'crop' => 'Crop',
            ],
            'table' => [
                'columns' => [
                    'title' => 'Title (:locale)',
                    'crops' => 'Crops',
                    'updated_at' => 'Updated at',
                    'general' => 'General',
                    'crop_fallback' => 'Crop #:id',
                ],
            ],
            'validation' => [
                'corrupted_token' => 'Matnda buzilgan token bor (":token") — saqlashdan oldin tuzating.',
                'comma_spacing' => 'Vergul keyin bo\'sh joy yo\'q — saqlashdan oldin tinish belgilarini tekshiring.',
            ],
        ],
    ],
];
