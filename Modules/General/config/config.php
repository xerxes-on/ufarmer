<?php

declare(strict_types=1);

return [
    'name' => 'General',
    'content' => [
        'media_disk' => env('CONTENT_MEDIA_DISK', 's3'),
        'media_path' => env('CONTENT_MEDIA_PATH', 'content/daily-updates'),
        'media_download_timeout' => (int) env('CONTENT_MEDIA_DOWNLOAD_TIMEOUT', 120),
        'mq_exchange' => env('CONTENT_PUBLISHED_EXCHANGE', 'content.published'),
        'yt_dlp_binary' => env('CONTENT_YT_DLP_BINARY', 'yt-dlp'),
        'crawl_timeout' => (int) env('CONTENT_CRAWL_TIMEOUT', 600),
        'ingest_token' => env('CONTENT_INGEST_TOKEN'),
    ],
];
