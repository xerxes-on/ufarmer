<?php

declare(strict_types=1);

return [
    'validation' => [
        'latitude_required' => 'Latitude is required.',
        'latitude_numeric' => 'Latitude must be a number.',
        'latitude_between' => 'Latitude must be between -90 and 90.',
        'longitude_required' => 'Longitude is required.',
        'longitude_numeric' => 'Longitude must be a number.',
        'longitude_between' => 'Longitude must be between -180 and 180.',
        'crop_id_required' => 'Crop ID is required.',
        'crop_id_exists' => 'The selected crop does not exist.',
        'crop_uuid_required' => 'Crop ID is required.',
        'crop_uuid_exists' => 'The selected crop does not exist.',
    ],
];
