<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\UserDetail;

final class NearbyUsersService
{
    /**
     * Find users within a given radius using the Haversine formula.
     *
     * The Haversine formula calculates the great-circle distance between two points
     * on a sphere given their longitudes and latitudes. This is used to find users
     * within a specified radius (in kilometers) from a given coordinate.
     *
     * @param  float  $latitude  The latitude of the search center point
     * @param  float  $longitude  The longitude of the search center point
     * @param  float  $radiusKm  The search radius in kilometers
     * @param  int  $perPage  Number of results per page
     * @return LengthAwarePaginator Paginated results with user details and calculated distance
     */
    public function findNearbyUsers(
        float $latitude,
        float $longitude,
        float $radiusKm,
        int $perPage
    ): LengthAwarePaginator {
        $haversineFormula = $this->buildHaversineFormula($latitude, $longitude);

        return UserDetail::query()
            ->select([
                'user_details.*',
                DB::raw("{$haversineFormula} AS distance_km"),
            ])
            ->with([
                'user:id,name,email',
                'region:id,name',
                'city:id,name,region_id',
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km', 'asc')
            ->paginate($perPage);
    }

    /**
     * Build the Haversine formula for calculating distance.
     *
     * Formula: 6371 * acos(cos(radians(lat1)) * cos(radians(lat2))
     *          * cos(radians(lng2) - radians(lng1))
     *          + sin(radians(lat1)) * sin(radians(lat2)))
     *
     * Where 6371 is Earth's radius in kilometers.
     *
     * @param  float  $latitude  The latitude of the search center
     * @param  float  $longitude  The longitude of the search center
     * @return string The SQL formula string
     */
    private function buildHaversineFormula(float $latitude, float $longitude): string
    {
        return sprintf(
            '(6371 * acos(cos(radians(%f)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(latitude))))',
            $latitude,
            $longitude,
            $latitude
        );
    }
}
