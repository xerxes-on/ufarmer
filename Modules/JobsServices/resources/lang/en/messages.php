<?php

declare(strict_types=1);

return [
    'validation' => [
        'price_fixed_required' => 'Fixed price is required when price mode is fixed.',
        'price_min_required' => 'Minimum price is required when price mode is range.',
        'deadline_required' => 'Deadline is required when timing type is deadline.',
        'fixed_time_required' => 'Fixed time is required when timing type is fixed.',
        'image_max_size' => 'Each image must not exceed 5MB.',
    ],
];
