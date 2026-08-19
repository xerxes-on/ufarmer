<?php

declare(strict_types=1);

return [
    'nearby_analysis_defaults' => [
        'enabled' => env('AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_ENABLED', true),
        'database_path' => env(
            'AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_DB',
            base_path('Modules/AgroCalendar/database/sqlite/nearby_analysis_defaults.sqlite'),
        ),
        'from_year' => (int) env('AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_FROM_YEAR', 2022),
        'to_year' => (int) env('AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_TO_YEAR', 2026),
        'radius_km' => (float) env('AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_RADIUS_KM', 10),
        'nearest_parcels' => (int) env('AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_NEAREST', 50),
        'minimum_latest_year_rows' => (int) env('AGROCALENDAR_NEARBY_ANALYSIS_DEFAULTS_MIN_LATEST_ROWS', 3),
    ],

    'soilgrids_defaults' => [
        'enabled' => env('AGROCALENDAR_SOILGRIDS_DEFAULTS_ENABLED', true),
        'database_path' => env(
            'AGROCALENDAR_SOILGRIDS_DEFAULTS_DB',
            base_path('Modules/AgroCalendar/database/sqlite/soilgrids_defaults.sqlite'),
        ),
        'radius_km' => (float) env('AGROCALENDAR_SOILGRIDS_DEFAULTS_RADIUS_KM', 15),
    ],
];
