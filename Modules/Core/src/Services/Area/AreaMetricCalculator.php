<?php

declare(strict_types=1);

namespace Modules\Core\Services\Area;

final class AreaMetricCalculator
{
    public const ATTRIBUTE_AREA = 'area';

    public const ATTRIBUTE_COORDINATES = 'coordinates';

    public const PIVOT_ACTIVE = 'active';

    public const PIVOT_DATE_STARTED = 'date_started';

    public const PIVOT_TABLE = 'area_crops';

    public const MINIMUM_POLYGON_POINTS = 3;

    public const AREA_PRECISION = 2;

    public const DEFAULT_COORDINATE_VALUE = 0.0;

    public const SQUARE_METERS_PER_HECTARE = 10000;

    public const EARTH_RADIUS_METERS = 6371000;

    public const SQUARE_METER_LABEL = ' m²';

    public const HECTARE_LABEL = ' ha';

    private const HALF = 0.5;

    public static function isPolygon(array $coordinates): bool
    {
        return count($coordinates) >= self::MINIMUM_POLYGON_POINTS;
    }

    public static function calculatePolygonArea(array $coordinates): float
    {
        if (! self::isPolygon($coordinates)) {
            return 0.0;
        }

        $points = $coordinates;
        if ($points[0] !== $points[count($points) - 1]) {
            $points[] = $points[0];
        }

        $area = 0.0;
        $pointCount = count($points);

        for ($index = 0; $index < $pointCount - 1; $index++) {
            $latitudeOne = deg2rad((float) $points[$index][0]);
            $longitudeOne = deg2rad((float) $points[$index][1]);
            $latitudeTwo = deg2rad((float) $points[$index + 1][0]);
            $longitudeTwo = deg2rad((float) $points[$index + 1][1]);

            $area += ($longitudeTwo - $longitudeOne) * (sin($latitudeOne) + sin($latitudeTwo));
        }

        $radiusSquared = self::EARTH_RADIUS_METERS * self::EARTH_RADIUS_METERS;

        return abs($area * $radiusSquared * self::HALF);
    }

    public static function calculateCenterPoint(array $coordinates): array
    {
        if ($coordinates === []) {
            return [self::DEFAULT_COORDINATE_VALUE, self::DEFAULT_COORDINATE_VALUE];
        }

        $latitudeSum = 0.0;
        $longitudeSum = 0.0;

        foreach ($coordinates as $coordinate) {
            $latitudeSum += (float) $coordinate[0];
            $longitudeSum += (float) $coordinate[1];
        }

        $coordinateCount = count($coordinates);

        return [
            $latitudeSum / $coordinateCount,
            $longitudeSum / $coordinateCount,
        ];
    }

    public static function formatArea(float $area): string
    {
        if ($area < self::SQUARE_METERS_PER_HECTARE) {
            return number_format($area, self::AREA_PRECISION).self::SQUARE_METER_LABEL;
        }

        return number_format($area / self::SQUARE_METERS_PER_HECTARE, self::AREA_PRECISION).self::HECTARE_LABEL;
    }
}
