<?php

namespace App\Features\Attendance\Clock;

use App\Features\Employees\Models\Branch;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Where an employee may use the web time clock. With no restriction on the
 * branch anyone may clock in; otherwise the request must come from an
 * allowed IP range OR from within the branch geofence.
 */
class ClockRestrictions
{
    /**
     * @return array{allowed: bool, distance_m: int|null, reason: string|null}
     */
    public function check(?Branch $branch, ?string $ip, ?float $latitude, ?float $longitude): array
    {
        $ranges = array_values(array_filter(array_map('trim', explode(',', (string) $branch?->allowed_ip_ranges))));
        $hasGeofence = $branch?->latitude !== null && $branch->longitude !== null && $branch->geofence_radius_m;

        if ($branch === null || ($ranges === [] && ! $hasGeofence)) {
            return ['allowed' => true, 'distance_m' => null, 'reason' => null];
        }

        if ($ranges !== [] && $ip !== null && IpUtils::checkIp($ip, $ranges)) {
            return ['allowed' => true, 'distance_m' => null, 'reason' => null];
        }

        if ($hasGeofence) {
            if ($latitude === null || $longitude === null) {
                return ['allowed' => false, 'distance_m' => null, 'reason' => 'Allow location access so we can confirm you are at '.$branch->name.'.'];
            }

            $distance = (int) round($this->distance((float) $branch->latitude, (float) $branch->longitude, $latitude, $longitude));

            return $distance <= $branch->geofence_radius_m
                ? ['allowed' => true, 'distance_m' => $distance, 'reason' => null]
                : ['allowed' => false, 'distance_m' => $distance, 'reason' => "You are about {$distance} m from {$branch->name}; clocking in is allowed within {$branch->geofence_radius_m} m."];
        }

        return ['allowed' => false, 'distance_m' => null, 'reason' => "Clocking in is only allowed from the {$branch->name} office network."];
    }

    public function requiresLocation(?Branch $branch): bool
    {
        return $branch?->latitude !== null && $branch->longitude !== null && (bool) $branch->geofence_radius_m;
    }

    /**
     * Great-circle distance in metres (haversine).
     */
    public function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6_371_000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
