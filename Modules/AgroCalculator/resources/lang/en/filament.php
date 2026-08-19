<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Agro Calculator',
    'locales' => [
        'en' => 'English',
        'ru' => 'Russian',
        'uz' => 'Uzbek',
    ],
    'pages' => [
        'run_calculator' => [
            'navigation' => 'Agro Calculator',
            'title' => 'Agro Calculator',
            'description' => 'Run agronomic calculations and inspect parameter sets.',
            'sections' => [
                'configuration' => 'Configuration',
                'inputs' => 'Inputs',
                'results' => 'Results',
                'parameters' => 'Parameter Overview',
            ],
            'fields' => [
                'crop' => 'Crop',
                'area_crop' => 'Area Crop',
            ],
            'helper' => [
                'default' => 'Default: :value',
                'options' => 'Options: :list',
                'factors' => 'Factors: :list',
            ],
            'factor_summaries' => [
                'range' => 'Range :min – :max',
                'optimal' => 'Optimal :value',
                'threshold' => 'Threshold :value',
                'target' => 'Target :value',
                'breakpoints' => ':count breakpoints',
                'pressure' => 'Reported or climate-derived pressure',
            ],
            'summary' => [
                'version' => 'Version',
                'valid_from' => 'Valid From',
                'valid_to' => 'Valid To',
                'baseline_yield' => 'Baseline Yield (t/ha)',
                'cycle_days' => 'Cycle Days',
                'defaults' => 'Defaults',
                'weights' => 'Weights',
                'factors' => 'Factors',
                'factor' => 'Factor',
                'weight' => 'Weight',
                'type' => 'Type',
                'config' => 'Config',
            ],
            'results' => [
                'potential_yield' => 'Potential Yield (t/ha)',
                'stress_index' => 'Stress Index',
                'risk_level' => 'Risk Level',
                'score' => 'Score',
                'grade' => 'Grade',
                'factors' => 'Factors',
                'value' => 'Value',
                'context' => 'Context',
                'metrics' => 'Metrics',
                'flags' => 'Flags',
                'recommendations' => 'Recommendations',
                'no_recommendations' => 'No recommendations were generated.',
            ],
            'labels' => [
                'unknown_crop' => 'Unknown crop',
                'unknown_area' => 'Unknown area',
            ],
            'actions' => [
                'calculate' => 'Run Calculation',
            ],
            'notifications' => [
                'validation' => [
                    'title' => 'Validation error',
                    'crop' => 'Select a crop before running the calculation.',
                    'area_crop' => 'Select an area crop to run the calculation.',
                ],
                'no_parameters' => [
                    'title' => 'Parameters not found',
                    'body' => 'No active parameter set was found for the selected crop.',
                ],
                'tables_missing' => 'Agro calculator tables are not available. Run the module migrations first.',
                'calculation_success' => [
                    'title' => 'Calculation completed',
                    'body' => 'Results are shown below.',
                ],
                'calculation_failed' => [
                    'title' => 'Calculation failed',
                ],
            ],
        ],
    ],
    'resources' => [
        'calculator_run' => [
            'navigation' => 'Calculator Runs',
            'label' => 'Calculator Run',
            'plural_label' => 'Calculator Runs',
            'table' => [
                'columns' => [
                    'id' => 'ID',
                    'crop' => 'Crop',
                    'yield' => 'Potential Yield (t/ha)',
                    'risk_level' => 'Risk Level',
                    'ran_at' => 'Ran At',
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
                'spec' => 'Specification',
                'meta' => 'Metadata',
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
                'meta' => 'Metadata',
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
        'recommendation_rule' => [
            'navigation' => 'Recommendation Rules',
            'label' => 'Recommendation Rule',
            'plural_label' => 'Recommendation Rules',
            'form' => [
                'code' => 'Code',
                'is_active' => 'Active',
                'title' => 'Title',
                'conditions' => 'Conditions',
                'recommendations' => 'Recommendations',
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
                'params' => 'Parameters',
                'meta' => 'Metadata',
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
