<?php

namespace App\Services;

use App\City;
use App\PricingZone;
use App\PricingTier;
use App\VehicleType;

class PricingService
{
    /**
     * Calculate shipping price based on origin city, distance, and vehicle type
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $distance Distance in kilometers
     * @param int $vehicleTypeId
     * @param bool $isInternal Whether this is internal (within city) or external shipping
     * @return float
     */
    public function calculatePrice(float $originLat, float $originLng, float $distance, int $vehicleTypeId, bool $isInternal = true): float
    {
        // Find the nearest city based on coordinates
        $city = $this->findNearestCity($originLat, $originLng);
        
        if (!$city) {
            throw new \Exception('No pricing zone found for this location');
        }

        // Get the appropriate pricing zone (Internal or External)
        $zoneType = $isInternal ? PricingZone::TYPE_INTERNAL : PricingZone::TYPE_EXTERNAL;
        $pricingZone = $city->pricingZones()->where('zone_type', $zoneType)->first();
        
        if (!$pricingZone) {
            throw new \Exception('No pricing zone configured for this city and type');
        }

        // Find the pricing tier for this distance
        $pricingTier = $pricingZone->pricingTiers()
            ->where('min_km', '<=', $distance)
            ->where('max_km', '>=', $distance)
            ->first();
            
        if (!$pricingTier) {
            throw new \Exception('No pricing tier found for distance: ' . $distance . ' km');
        }

        // Get vehicle type multiplier
        $vehicleType = VehicleType::findOrFail($vehicleTypeId);
        
        // Calculate final price: base_price * vehicle_type_multiplier
        $finalPrice = $pricingTier->base_price * $vehicleType->pricing_multiplier;
        
        return round($finalPrice, 2);
    }

    /**
     * Find the nearest city based on coordinates
     * This is a simplified version - in production, you'd use proper geographical distance calculation
     *
     * @param float $lat
     * @param float $lng
     * @return City|null
     */
    private function findNearestCity(float $lat, float $lng): ?City
    {
        // For now, find the city with the smallest distance using Haversine formula approximation
        $cities = City::all();
        $nearestCity = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($cities as $city) {
            $distance = $this->calculateDistanceBetweenCoordinates(
                $lat, $lng, 
                (float)$city->lat, (float)$city->lng
            );
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestCity = $city;
            }
        }

        return $nearestCity;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in kilometers
     */
    public function calculateDistanceBetweenCoordinates(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Determine if shipping is internal (within same city) or external
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $destLat
     * @param float $destLng
     * @return bool
     */
    public function isInternalShipping(float $originLat, float $originLng, float $destLat, float $destLng): bool
    {
        $originCity = $this->findNearestCity($originLat, $originLng);
        $destCity = $this->findNearestCity($destLat, $destLng);

        return $originCity && $destCity && $originCity->id === $destCity->id;
    }
}