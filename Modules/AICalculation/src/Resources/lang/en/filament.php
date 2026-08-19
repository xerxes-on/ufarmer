<?php

declare(strict_types=1);

return [
    'navigation_group' => 'AI Calculations',

    'resources' => [
        'ai_calculation_request' => [
            'navigation' => 'AI Calculation Requests',
            'label' => 'AI Calculation Request',
            'plural_label' => 'AI Calculation Requests',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'job_uuid' => 'Job UUID',
                    'user' => 'User',
                    'area' => 'Area',
                    'status' => 'Status',
                    'crops_count' => 'Crops',
                    'soil_docs_count' => 'Soil Docs',
                    'water_docs_count' => 'Water Docs',
                    'farming_start_date' => 'Farming Start Date',
                    'submitted_at' => 'Submitted At',
                    'completed_at' => 'Completed At',
                ],
            ],

            'view' => [
                'request_info' => 'Request Information',
                'crops' => 'Requested Crops',
                'soil_documents' => 'Soil Documents',
                'water_documents' => 'Water Documents',
                'result' => 'AI Result',
                'alternative_crops' => 'Alternative Crops',
                'error' => 'Error Information',

                'sent_to_n8n_at' => 'Sent to n8n At',
                'crop_name' => 'Crop Name',
                'variety_name' => 'Variety',
                'planting_date' => 'Planting Date',
                'filename' => 'Filename',
                'file_size' => 'Size',
                'url' => 'URL',
                'overall_rating' => 'Overall Rating',
                'confidence_score' => 'Confidence Score',
                'summary' => 'Summary',
                'rank' => 'Rank',
                'suitability_score' => 'Suitability Score',
                'reasoning' => 'Reasoning',
                'error_details' => 'Error Details',
            ],
        ],
    ],
];
