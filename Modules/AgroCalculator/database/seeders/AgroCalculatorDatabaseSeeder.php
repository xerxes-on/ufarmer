<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AgroCalculator\Models\CropParameterSet;
use Modules\AgroCalculator\Models\RecommendationRule;
use Modules\AgroCalculator\Models\ScoringModel;
use Modules\AgroCalculator\Models\ScoringThreshold;
use Modules\Crops\Models\Crop;

class AgroCalculatorDatabaseSeeder extends Seeder
{
    private const CROP_NAME = 'Tomato';

    private const CROP_CODE = 'TOM';

    public function run(): void
    {
        $crop = Crop::query()->firstOrCreate(
            ['code' => self::CROP_CODE],
            ['name' => ['en' => self::CROP_NAME]]
        );

        $parameterSet = CropParameterSet::query()->firstOrCreate(
            ['crop_id' => $crop->id, 'version' => 1],
            [
                'params' => $this->defaultParameterSet(),
                'meta' => ['source' => 'seed'],
                'is_active' => true,
            ]
        );

        $scoringModel = ScoringModel::query()->firstOrCreate(
            ['code' => 'AGRO_SCORE_V1'],
            [
                'name' => 'Agronomic Score v1',
                'scope' => 'agronomic',
                'spec' => [
                    'agronomic' => [
                        'weights' => [
                            'soil_ph' => 1.2,
                            'humus' => 1.0,
                            'nitrogen' => 1.0,
                            'phosphorus' => 1.0,
                            'potassium' => 1.0,
                            'soil_ec' => 1.1,
                            'water_balance' => 1.0,
                            'temperature' => 1.0,
                            'water_ph' => 0.8,
                            'nematodes' => 0.8,
                            'disease' => 1.1,
                            'pest' => 1.1,
                            'weed' => 0.5,
                        ],
                    ],
                    'flags' => [
                        'factor_threshold' => 0.75,
                    ],
                ],
                'meta' => ['source' => 'seed'],
                'is_active' => true,
            ]
        );

        ScoringThreshold::query()->firstOrCreate(
            ['scoring_model_id' => $scoringModel->id, 'metric_key' => 'score', 'label' => 'high'],
            ['min_value' => 80, 'max_value' => 100]
        );
        ScoringThreshold::query()->firstOrCreate(
            ['scoring_model_id' => $scoringModel->id, 'metric_key' => 'score', 'label' => 'medium'],
            ['min_value' => 60, 'max_value' => 79.99]
        );
        ScoringThreshold::query()->firstOrCreate(
            ['scoring_model_id' => $scoringModel->id, 'metric_key' => 'score', 'label' => 'low'],
            ['min_value' => 0, 'max_value' => 59.99]
        );

        RecommendationRule::query()->firstOrCreate(
            ['code' => 'PH_CORRECTION'],
            [
                'title' => [
                    'en' => 'Adjust Soil pH',
                    'uz' => 'Tuproq pH ni sozlash',
                ],
                'conditions' => [
                    'factors.soil_ph.score' => ['<' => 0.9],
                ],
                'recommendations' => [
                    'en' => 'Apply lime to raise soil pH into the optimal range.',
                ],
                'is_active' => true,
            ]
        );
    }

    private function defaultParameterSet(): array
    {
        return [
            'baseline_yield' => 120,
            'cycle_days' => 120,
            'defaults' => [
                'weights' => [
                    'default' => 1.0,
                ],
                'risk' => [
                    'default_label' => 'high',
                ],
                'pressure' => [
                    'threshold_low' => 0.34,
                    'threshold_medium' => 0.67,
                    'score_optimal' => 1.0,
                    'score_low' => 0.9,
                    'score_medium' => 0.75,
                    'score_high' => 0.55,
                ],
            ],
            'weights' => [
                'soil_ph' => 1.2,
                'humus' => 1.0,
                'nitrogen' => 1.0,
                'phosphorus' => 1.0,
                'potassium' => 1.0,
                'soil_ec' => 1.1,
                'water_balance' => 1.0,
                'temperature' => 1.0,
                'water_ph' => 0.8,
                'nematodes' => 0.8,
                'disease' => 1.1,
                'pest' => 1.1,
                'weed' => 0.5,
            ],
            'inputs' => [
                'sources' => [
                    'soil' => 'soil_inputs',
                    'water' => 'water_inputs',
                    'climate' => 'climate_inputs',
                    'management' => 'management_inputs',
                ],
                'required' => [
                    'soil' => ['humus', 'ph', 'n', 'p', 'k', 'ec', 'nematodes_j2_perkg'],
                    'water' => ['ph'],
                    'climate' => ['avg_temp', 'rh'],
                    'management' => ['water_available'],
                ],
            ],
            'factors' => [
                [
                    'name' => 'humus',
                    'type' => 'band',
                    'input' => ['category' => 'soil', 'key' => 'humus'],
                    'config' => ['min' => 3, 'max' => 5],
                ],
                [
                    'name' => 'soil_ph',
                    'type' => 'band',
                    'input' => ['category' => 'soil', 'key' => 'ph'],
                    'config' => ['min' => 6.0, 'max' => 6.8],
                ],
                [
                    'name' => 'nitrogen',
                    'type' => 'ratio_piecewise',
                    'input' => ['category' => 'soil', 'key' => 'n'],
                    'config' => [
                        'optimal' => 125,
                        'bands' => [
                            ['min' => 0.8, 'max' => 1.2, 'value' => 1],
                            ['min' => 0.5, 'max' => 0.8, 'value' => 0.8],
                            ['min' => 1.2, 'max' => 1.5, 'value' => 0.8],
                            ['min' => 0.3, 'max' => 0.5, 'value' => 0.6],
                            ['min' => 1.5, 'max' => 2.0, 'value' => 0.6],
                        ],
                        'fallback' => 0.3,
                    ],
                ],
                [
                    'name' => 'phosphorus',
                    'type' => 'ratio_piecewise',
                    'input' => ['category' => 'soil', 'key' => 'p'],
                    'config' => [
                        'optimal' => 90,
                        'bands' => [
                            ['min' => 0.8, 'max' => 1.2, 'value' => 1],
                            ['min' => 0.5, 'max' => 0.8, 'value' => 0.8],
                            ['min' => 1.2, 'max' => 1.5, 'value' => 0.8],
                            ['min' => 0.3, 'max' => 0.5, 'value' => 0.6],
                            ['min' => 1.5, 'max' => 2.0, 'value' => 0.6],
                        ],
                        'fallback' => 0.3,
                    ],
                ],
                [
                    'name' => 'potassium',
                    'type' => 'ratio_piecewise',
                    'input' => ['category' => 'soil', 'key' => 'k'],
                    'config' => [
                        'optimal' => 200,
                        'bands' => [
                            ['min' => 0.8, 'max' => 1.2, 'value' => 1],
                            ['min' => 0.5, 'max' => 0.8, 'value' => 0.8],
                            ['min' => 1.2, 'max' => 1.5, 'value' => 0.8],
                            ['min' => 0.3, 'max' => 0.5, 'value' => 0.6],
                            ['min' => 1.5, 'max' => 2.0, 'value' => 0.6],
                        ],
                        'fallback' => 0.3,
                    ],
                ],
                [
                    'name' => 'soil_ec',
                    'type' => 'salinity',
                    'input' => ['category' => 'soil', 'key' => 'ec'],
                    'config' => ['threshold' => 2.5, 'slope' => 0.1],
                ],
                [
                    'name' => 'water_balance',
                    'type' => 'water_balance',
                    'input' => ['category' => 'management', 'key' => 'water_available'],
                    'config' => ['optimal' => 500, 'ky' => 0.4],
                ],
                [
                    'name' => 'temperature',
                    'type' => 'temperature_triangle',
                    'input' => ['category' => 'climate', 'key' => 'avg_temp'],
                    'config' => ['min' => 18, 'max' => 25, 'center' => 21.5],
                ],
                [
                    'name' => 'water_ph',
                    'type' => 'band',
                    'input' => ['category' => 'water', 'key' => 'ph'],
                    'config' => ['min' => 6.5, 'max' => 7.5],
                ],
                [
                    'name' => 'nematodes',
                    'type' => 'breakpoints',
                    'input' => ['category' => 'soil', 'key' => 'nematodes_j2_perkg'],
                    'config' => [
                        'breakpoints' => [
                            ['max' => 200, 'value' => 0.88],
                            ['max' => 400, 'value' => 0.77],
                            ['max' => 800, 'value' => 0.6],
                        ],
                        'fallback' => 0.4,
                    ],
                ],
                [
                    'name' => 'disease',
                    'type' => 'pressure',
                    'config' => [
                        'reported_input' => ['category' => 'management', 'key' => 'disease_pressure'],
                        'climate' => [
                            'humidity_input' => ['category' => 'climate', 'key' => 'rh'],
                            'temperature_input' => ['category' => 'climate', 'key' => 'avg_temp'],
                            'humidity_bands' => ['70' => 0.9, '80' => 0.8, '90' => 0.6],
                            'humidity_trigger' => 80,
                            'temperature_threshold' => 25,
                            'fallback' => 0.7,
                        ],
                    ],
                ],
                [
                    'name' => 'pest',
                    'type' => 'pressure',
                    'config' => [
                        'reported_input' => ['category' => 'management', 'key' => 'pest_pressure'],
                        'climate' => [
                            'humidity_input' => ['category' => 'climate', 'key' => 'rh'],
                            'temperature_input' => ['category' => 'climate', 'key' => 'avg_temp'],
                            'humidity_bands' => ['80' => 0.85, '90' => 0.7],
                            'humidity_trigger' => 80,
                            'temperature_threshold' => 25,
                            'fallback' => 0.7,
                        ],
                    ],
                ],
                [
                    'name' => 'weed',
                    'type' => 'constant',
                    'config' => ['score' => 0.9],
                ],
            ],
            'risk' => [
                'stress_factors' => ['soil_ec', 'temperature', 'disease', 'pest'],
                'bands' => [
                    ['max' => 0.15, 'label' => 'low'],
                    ['max' => 0.35, 'label' => 'medium'],
                    ['max' => 1.0, 'label' => 'high'],
                ],
            ],
            'dependencies' => [],
        ];
    }
}
