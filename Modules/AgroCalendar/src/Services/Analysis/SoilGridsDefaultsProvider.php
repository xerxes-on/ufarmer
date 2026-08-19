<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Services\Analysis;

use PDO;
use PDOException;

/**
 * Field-resolution soil PHYSICS (texture, field capacity, wilting point, bulk
 * density) sampled from a self-hosted SoilGrids grid. Complements the agrolab
 * chemistry layer — SoilGrids has no reliable available N/P/K, and the agrolab
 * parcels have no water-retention data. See build_soilgrids_grid.py for the
 * grid asset.
 */
class SoilGridsDefaultsProvider
{
    private ?PDO $pdo = null;

    /**
     * @return array<string, mixed> flat {AnalysisParamKey value => value}
     */
    public function get(float $latitude, float $longitude): array
    {
        if (! (bool) config('agrocalendar.soilgrids_defaults.enabled', true)) {
            return [];
        }

        $databasePath = (string) config('agrocalendar.soilgrids_defaults.database_path', '');
        if ($databasePath === '' || ! is_file($databasePath)) {
            return [];
        }

        $pdo = $this->connection($databasePath);
        if ($pdo === null) {
            return [];
        }

        try {
            $cell = $this->nearestCell($pdo, $latitude, $longitude);
        } catch (PDOException) {
            return [];
        }

        if ($cell === null) {
            return [];
        }

        return $this->toPhysicalDefaults($cell);
    }

    private function connection(string $databasePath): ?PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        try {
            $this->pdo = new PDO('sqlite:'.$databasePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException) {
            return null;
        }

        return $this->pdo;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nearestCell(PDO $pdo, float $latitude, float $longitude): ?array
    {
        $radiusKm = (float) config('agrocalendar.soilgrids_defaults.radius_km', 15);
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / max(1.0, 111.0 * cos(deg2rad($latitude)));

        $statement = $pdo->prepare(
            'SELECT centroid_lat, centroid_lng, theta_fc, theta_wp, bulk_density, texture_class
             FROM grid_cells
             WHERE centroid_lng BETWEEN :min_lng AND :max_lng
               AND centroid_lat BETWEEN :min_lat AND :max_lat'
        );
        $statement->execute([
            'min_lng' => $longitude - $lngDelta,
            'max_lng' => $longitude + $lngDelta,
            'min_lat' => $latitude - $latDelta,
            'max_lat' => $latitude + $latDelta,
        ]);

        $nearest = null;
        $nearestDistance = INF;
        foreach ($statement->fetchAll() as $row) {
            $distance = $this->haversineKm(
                $longitude,
                $latitude,
                (float) $row['centroid_lng'],
                (float) $row['centroid_lat'],
            );

            if ($distance <= $radiusKm && $distance < $nearestDistance) {
                $nearest = $row;
                $nearestDistance = $distance;
            }
        }

        return $nearest;
    }

    /**
     * @param  array<string, mixed>  $cell
     * @return array<string, mixed>
     */
    private function toPhysicalDefaults(array $cell): array
    {
        $details = [];
        $map = [
            'theta_fc' => 'FC',
            'theta_wp' => 'WP',
            'bulk_density' => 'bulk_density',
        ];
        foreach ($map as $column => $paramKey) {
            if (is_numeric($cell[$column] ?? null)) {
                $details[$paramKey] = round((float) $cell[$column], 3);
            }
        }

        $texture = $cell['texture_class'] ?? null;
        if (is_string($texture) && $texture !== '') {
            $details['texture'] = $texture;
        }

        return $details;
    }

    private function haversineKm(float $leftLongitude, float $leftLatitude, float $rightLongitude, float $rightLatitude): float
    {
        $earthRadiusKm = 6371.0088;
        $latDelta = deg2rad($rightLatitude - $leftLatitude);
        $lngDelta = deg2rad($rightLongitude - $leftLongitude);
        $haversine = sin($latDelta / 2) ** 2
            + cos(deg2rad($leftLatitude)) * cos(deg2rad($rightLatitude)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadiusKm * asin(sqrt($haversine));
    }
}
