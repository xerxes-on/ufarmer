<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Services\Analysis;

use Carbon\CarbonImmutable;
use Modules\AgroCalendar\Enums\AnalysisParamKey;
use Modules\AgroCalendar\Enums\AnalysisType;
use Modules\AgroCalendar\Models\AreaAnalysis;
use Modules\AgroCalendar\Models\RegionAnalysisDefault;
use Modules\Core\Models\Area;

final class CalendarSoilAnalysisResolver
{
    public function __construct(
        private readonly NearbyAnalysisDefaultsProvider $nearbyDefaultsProvider,
        private readonly SoilGridsDefaultsProvider $soilGridsDefaultsProvider,
    ) {}

    /**
     * Resolve the same soil analysis entry sent in command.generate.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(Area $area): ?array
    {
        $analysis = AreaAnalysis::query()
            ->where('area_id', $area->getKey())
            ->where('type', AnalysisType::Soil->value)
            ->confirmed()
            ->latestFirst()
            ->first();

        if ($analysis !== null) {
            return [
                'type' => AnalysisType::Soil->value,
                'analysis_date' => $analysis->analysis_date->toDateString(),
                'lab_name' => $analysis->lab_name,
                'source' => 'user_confirmed',
                'details' => $this->groupDetails($analysis->details ?? []),
            ];
        }

        $defaults = $area->region_id !== null
            ? RegionAnalysisDefault::query()
                ->where('region_id', $area->region_id)
                ->where('type', AnalysisType::Soil->value)
                ->value('details') ?? []
            : [];

        [$latitude, $longitude] = $this->coordinates($area);
        $nearby = $latitude !== null && $longitude !== null
            ? $this->nearbyDefaultsProvider->get($latitude, $longitude)
            : [];
        if ($nearby !== []) {
            $defaults = array_replace($defaults, $nearby);
        }

        $soilgrids = $latitude !== null && $longitude !== null
            ? $this->soilGridsDefaultsProvider->get($latitude, $longitude)
            : [];
        if ($soilgrids !== []) {
            $defaults = array_replace($defaults, $soilgrids);
        }

        if ($defaults === []) {
            return null;
        }

        return [
            'type' => AnalysisType::Soil->value,
            'analysis_date' => CarbonImmutable::today()->toDateString(),
            'lab_name' => null,
            'source' => $this->defaultsSource($area->region_id, $nearby, $soilgrids),
            'details' => $this->groupDetails($defaults),
        ];
    }

    /**
     * @return array{?float, ?float}
     */
    private function coordinates(Area $area): array
    {
        $latitude = $area->coordinates[0][0] ?? null;
        $longitude = $area->coordinates[0][1] ?? null;

        return [
            is_numeric($latitude) ? (float) $latitude : null,
            is_numeric($longitude) ? (float) $longitude : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $nearby
     * @param  array<string, mixed>  $soilgrids
     */
    private function defaultsSource(?int $regionId, array $nearby, array $soilgrids): string
    {
        $chemistry = match (true) {
            $nearby !== [] => 'nearby_cadastre_default',
            $regionId !== null => 'region_default',
            default => 'soilgrids',
        };

        return $soilgrids !== [] && $chemistry !== 'soilgrids'
            ? $chemistry.'+soilgrids'
            : $chemistry;
    }

    /**
     * @param  array<string, mixed>  $flat
     * @return array<string, array<string, mixed>>
     */
    private function groupDetails(array $flat): array
    {
        $grouped = [];
        foreach ($flat as $paramValue => $value) {
            if (! is_string($paramValue) || $value === null || $value === '') {
                continue;
            }

            $paramKey = AnalysisParamKey::tryFrom($paramValue);
            if ($paramKey === null) {
                continue;
            }

            $grouped[$paramKey->detailGroup(AnalysisType::Soil)->value][$paramKey->value] = $value;
        }

        return $grouped;
    }
}
