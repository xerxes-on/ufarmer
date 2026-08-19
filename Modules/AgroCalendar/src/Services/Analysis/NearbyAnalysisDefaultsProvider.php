<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Services\Analysis;

use PDO;
use PDOException;

class NearbyAnalysisDefaultsProvider
{
    private ?PDO $pdo = null;

    /**
     * @return array<string, mixed>
     */
    public function get(float $latitude, float $longitude): array
    {
        if (! (bool) config('agrocalendar.nearby_analysis_defaults.enabled', true)) {
            return [];
        }

        $databasePath = (string) config('agrocalendar.nearby_analysis_defaults.database_path', '');
        if ($databasePath === '' || ! is_file($databasePath)) {
            return [];
        }

        $pdo = $this->connection($databasePath);
        if ($pdo === null) {
            return [];
        }

        try {
            $candidateHashes = $this->candidateHashes($pdo, $latitude, $longitude);
            if ($candidateHashes === []) {
                return [];
            }

            $rows = $this->labRows($pdo, $candidateHashes);
            if ($rows === []) {
                return [];
            }
        } catch (PDOException) {
            return [];
        }

        return $this->toAnalysisDefaults($rows);
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
     * @return array<int, string>
     */
    private function candidateHashes(PDO $pdo, float $latitude, float $longitude): array
    {
        return $this->nearbyParcelHashes($pdo, $latitude, $longitude);
    }

    /**
     * @return array<int, string>
     */
    private function nearbyParcelHashes(PDO $pdo, float $latitude, float $longitude): array
    {
        $radiusKm = (float) config('agrocalendar.nearby_analysis_defaults.radius_km', 10);
        $nearest = max(1, (int) config('agrocalendar.nearby_analysis_defaults.nearest_parcels', 50));
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / max(1.0, 111.0 * cos(deg2rad($latitude)));

        $statement = $pdo->prepare(
            'SELECT cadastre_hash, centroid_lng, centroid_lat
             FROM parcel_geometries
             WHERE centroid_lng BETWEEN :min_lng AND :max_lng
               AND centroid_lat BETWEEN :min_lat AND :max_lat'
        );
        $statement->execute([
            'min_lng' => $longitude - $lngDelta,
            'max_lng' => $longitude + $lngDelta,
            'min_lat' => $latitude - $latDelta,
            'max_lat' => $latitude + $latDelta,
        ]);

        $candidates = [];
        foreach ($statement->fetchAll() as $row) {
            $distance = $this->haversineKm(
                $longitude,
                $latitude,
                (float) $row['centroid_lng'],
                (float) $row['centroid_lat'],
            );

            if ($distance <= $radiusKm) {
                $candidates[] = [
                    'hash' => (string) $row['cadastre_hash'],
                    'distance' => $distance,
                ];
            }
        }

        usort($candidates, static fn (array $left, array $right): int => $left['distance'] <=> $right['distance']);

        return array_map(
            static fn (array $row): string => $row['hash'],
            array_slice($candidates, 0, $nearest),
        );
    }

    /**
     * @param  array<int, string>  $hashes
     * @return array<int, array<string, mixed>>
     */
    private function labRows(PDO $pdo, array $hashes): array
    {
        if ($hashes === []) {
            return [];
        }

        $fromYear = (int) config('agrocalendar.nearby_analysis_defaults.from_year', 2022);
        $toYear = (int) config('agrocalendar.nearby_analysis_defaults.to_year', 2026);
        $placeholders = implode(',', array_fill(0, count($hashes), '?'));
        $sql = "SELECT year, humus, phosphorus_pentoxide, potassium, nitrate, hydrogen
                FROM lab_observations
                WHERE cadastre_hash IN ({$placeholders})
                  AND year BETWEEN ? AND ?
                ORDER BY year DESC";

        $statement = $pdo->prepare($sql);
        $statement->execute([...$hashes, $fromYear, $toYear]);

        return $statement->fetchAll();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function toAnalysisDefaults(array $rows): array
    {
        $latestYear = max(array_map(static fn (array $row): int => (int) $row['year'], $rows));
        $latestRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => (int) $row['year'] === $latestYear,
        ));
        $minimumLatestRows = (int) config('agrocalendar.nearby_analysis_defaults.minimum_latest_year_rows', 3);
        $sourceRows = count($latestRows) >= $minimumLatestRows ? $latestRows : $rows;

        $details = [];
        $organicMatter = $this->medianColumn($sourceRows, 'humus', min: 0.01);
        if ($organicMatter !== null) {
            $details['organic_matter_percent'] = $organicMatter;
        }

        $phosphorus = $this->medianColumn($sourceRows, 'phosphorus_pentoxide', min: 0.01);
        if ($phosphorus !== null) {
            $details['P_mg_kg'] = $phosphorus;
        }

        $potassium = $this->medianColumn($sourceRows, 'potassium', min: 0.01);
        if ($potassium !== null) {
            $details['K_mg_kg'] = $potassium;
        }

        $nitrogen = $this->medianColumn($sourceRows, 'nitrate', min: 0.01);
        if ($nitrogen !== null) {
            $details['N_mg_kg'] = $nitrogen;
        }

        $ph = $this->medianColumn($sourceRows, 'hydrogen', min: 3.0, max: 10.0);
        if ($ph !== null) {
            $details['pH'] = $ph;
        }

        return $details;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function medianColumn(array $rows, string $column, ?float $min = null, ?float $max = null): ?float
    {
        $values = [];
        foreach ($rows as $row) {
            if (! is_numeric($row[$column] ?? null)) {
                continue;
            }

            $value = (float) $row[$column];
            if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
                continue;
            }

            $values[] = $value;
        }

        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);
        $median = count($values) % 2 === 0
            ? ($values[$middle - 1] + $values[$middle]) / 2
            : $values[$middle];

        return round($median, 3);
    }

    private function haversineKm(float $leftLongitude, float $leftLatitude, float $rightLongitude, float $rightLatitude): float
    {
        $earthRadiusKm = 6371.0088;
        $latDelta = deg2rad($rightLatitude - $leftLatitude);
        $lngDelta = deg2rad($rightLongitude - $leftLongitude);
        $leftLat = deg2rad($leftLatitude);
        $rightLat = deg2rad($rightLatitude);
        $haversine = sin($latDelta / 2) ** 2
            + cos($leftLat) * cos($rightLat) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadiusKm * asin(sqrt($haversine));
    }
}
