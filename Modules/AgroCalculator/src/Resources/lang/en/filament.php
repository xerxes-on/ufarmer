<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Agro Calculator',

    'resources' => [
        'calculator_run' => [
            'navigation' => 'Calculator Runs',
            'label' => 'Calculator Run',
            'plural_label' => 'Calculator Runs',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Crop',
                    'yield' => 'Yield (t/ha)',
                    'risk_level' => 'Risk Level',
                    'ran_at' => 'Ran At',
                ],
            ],
        ],

        'scoring_model' => [
            'navigation' => 'Scoring Models',
            'label' => 'Scoring Model',
            'plural_label' => 'Scoring Models',

            'form' => [
                'name' => 'Name',
                'code' => 'Code',
                'scope' => 'Scope',
                'version' => 'Version',
                'is_active' => 'Active',
                'valid_from' => 'Valid From',
                'valid_to' => 'Valid To',
                'spec' => 'Specification JSON',
                'meta' => 'Meta JSON',
            ],

            'table' => [
                'columns' => [
                    'name' => 'Name',
                    'code' => 'Code',
                    'version' => 'Version',
                    'is_active' => 'Active',
                    'valid_from' => 'Valid From',
                    'valid_to' => 'Valid To',
                ],
            ],
        ],

        'scoring_run' => [
            'navigation' => 'Scoring Runs',
            'label' => 'Scoring Run',
            'plural_label' => 'Scoring Runs',

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Crop',
                    'score' => 'Score',
                    'grade' => 'Grade',
                    'created_at' => 'Created At',
                ],
            ],
        ],

        'recommendation_rule' => [
            'navigation' => 'Recommendation Rules',
            'label' => 'Recommendation Rule',
            'plural_label' => 'Recommendation Rules',

            'form' => [
                'code' => 'Code',
                'is_active' => 'Active',
                'title' => 'Title Translations',
                'conditions' => 'Conditions JSON',
                'recommendations' => 'Recommendations JSON',
            ],

            'table' => [
                'columns' => [
                    'code' => 'Code',
                    'title_en' => 'Title (EN)',
                    'is_active' => 'Active',
                    'updated' => 'Updated',
                ],
            ],
        ],

        'scoring_threshold' => [
            'navigation' => 'Scoring Thresholds',
            'label' => 'Scoring Threshold',
            'plural_label' => 'Scoring Thresholds',

            'form' => [
                'scoring_model_id' => 'Scoring Model',
                'metric_key' => 'Metric Key',
                'min_value' => 'Min Value',
                'max_value' => 'Max Value',
                'label' => 'Label',
                'meta' => 'Meta',
            ],

            'table' => [
                'columns' => [
                    'model' => 'Model',
                    'metric_key' => 'Metric Key',
                    'min_value' => 'Min Value',
                    'max_value' => 'Max Value',
                    'label' => 'Label',
                ],
            ],
        ],

        'crop_parameter_set' => [
            'navigation' => 'Crop Parameter Sets',
            'label' => 'Crop Parameter Set',
            'plural_label' => 'Crop Parameter Sets',

            'form' => [
                'crop_id' => 'Crop',
                'version' => 'Version',
                'is_active' => 'Active',
                'valid_from' => 'Valid From',
                'valid_to' => 'Valid To',
                'params' => 'Parameters (JSON)',
                'meta' => 'Meta (JSON)',
            ],

            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Crop',
                    'version' => 'Version',
                    'is_active' => 'Active',
                    'valid_from' => 'Valid From',
                    'valid_to' => 'Valid To',
                    'created' => 'Created',
                ],
            ],
        ],
    ],
];
