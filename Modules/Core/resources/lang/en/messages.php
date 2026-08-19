<?php

declare(strict_types=1);

return [
    'auth' => [
        'unauthorized' => 'Unauthorized.',
    ],
    'area' => [
        'created' => 'Area created successfully.',
        'updated' => 'Area updated successfully.',
        'deleted' => 'Area deleted successfully.',
        'unit_m2' => 'm²',
        'unit_ha' => 'ha',
    ],
    'area_crop' => [
        'attached' => 'Crop attached to area successfully.',
        'updated' => 'Area crop updated successfully.',
        'detached' => 'Crop detached from area successfully.',
        'none_attached' => 'No valid crops to attach.',
        'activated' => 'Area crop activated successfully.',
        'deactivated' => 'Area crop deactivated successfully.',
        'not_found' => 'Crop not attached to this area.',
        'missing_crop' => 'Crop not found.',
        'inactive_crop' => 'Crop not found or inactive.',
        'harvested' => 'Crop harvested successfully.',
        'already_harvested' => 'This crop has already been harvested.',
        'insufficient_space' => 'Insufficient space available. Available: :available ha, Requested: :requested ha',
        'exceeds_total' => 'Allocated area cannot exceed total area.',
        'invalid_area' => 'Area must be greater than 0.',
    ],
    'region' => [
        'not_found' => 'Region not found.',
    ],
    'user_detail' => [
        'created' => 'User details created successfully.',
        'updated' => 'User details updated successfully.',
        'deleted' => 'User details deleted successfully.',
        'already_exists' => 'User details already exist. Use PUT to update.',
        'not_found' => 'User details not found.',
        'image_deleted' => 'Image deleted successfully.',
        'city_mismatch' => 'The selected city does not belong to the selected region.',
        'no_image' => 'No image found to delete.',
    ],
    'user' => [
        'not_found' => 'User not found.',
    ],
    'nearby_users' => [
        'found' => 'Found :count users within :radius km.',
        'none_found' => 'No users found within the specified radius.',
        'invalid_coordinates' => 'Invalid coordinates provided.',
    ],
];
