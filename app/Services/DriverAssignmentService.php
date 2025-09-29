<?php

namespace App\Services;

use App\Driver;
use App\Shipment;
use App\VehicleType;
use Illuminate\Support\Collection;

class DriverAssignmentService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Find and notify available drivers for a new shipment
     * Implements the First-to-Accept model
     *
     * @param Shipment $shipment
     * @param float $maxRadiusKm Maximum radius to search for drivers
     * @return array List of notified driver IDs
     */
    public function findAndNotifyDrivers(Shipment $shipment, float $maxRadiusKm = 50): array
    {
        // Find eligible drivers based on location, availability, and vehicle capacity
        $eligibleDrivers = $this->findEligibleDrivers(
            $shipment->origin_lat,
            $shipment->origin_lng,
            $shipment->weight,
            $maxRadiusKm
        );

        if ($eligibleDrivers->isEmpty()) {
            throw new \Exception('No available drivers found for this shipment');
        }

        // Notify all eligible drivers about the new shipment
        $notifiedDriverIds = [];
        foreach ($eligibleDrivers as $driver) {
            try {
                $this->notificationService->notifyDriverOfNewShipment($driver, $shipment);
                $notifiedDriverIds[] = $driver->id;
            } catch (\Exception $e) {
                // Log the error but continue notifying other drivers
                \Log::error('Failed to notify driver ' . $driver->id . ': ' . $e->getMessage());
            }
        }

        return $notifiedDriverIds;
    }

    /**
     * Find eligible drivers based on location, availability, and vehicle capacity
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $packageWeight
     * @param float $maxRadiusKm
     * @return Collection<Driver>
     */
    public function findEligibleDrivers(float $originLat, float $originLng, float $packageWeight, float $maxRadiusKm): Collection
    {
        // Get all available and verified drivers with their vehicles
        $availableDrivers = Driver::with(['user', 'vehicle.vehicleType'])
            ->where('is_verified', true)
            ->where('status', Driver::STATUS_AVAILABLE)
            ->whereHas('vehicle', function ($query) use ($packageWeight) {
                // Check if driver's vehicle can carry the package weight
                $query->where('max_weight', '>=', $packageWeight);
            })
            ->get();

        // Filter drivers by proximity (this is simplified - in production you'd use spatial queries)
        $eligibleDrivers = $availableDrivers->filter(function ($driver) use ($originLat, $originLng, $maxRadiusKm) {
            // For now, we'll assume all available drivers are within range
            // In production, you'd:
            // 1. Store driver's current location in the database
            // 2. Use spatial queries or external services to find nearby drivers
            // 3. Consider traffic conditions and estimated arrival time
            
            return true; // Placeholder - implement actual proximity check
        });

        return $eligibleDrivers;
    }

    /**
     * Assign a driver to a shipment (called when driver accepts)
     *
     * @param Shipment $shipment
     * @param Driver $driver
     * @return bool
     */
    public function assignDriverToShipment(Shipment $shipment, Driver $driver): bool
    {
        // Check if shipment is still available for assignment
        if ($shipment->status !== Shipment::STATUS_PENDING) {
            return false;
        }

        // Check if driver is still available
        if (!$driver->isAvailable()) {
            return false;
        }

        // Double-check vehicle capacity
        if ($driver->vehicle && !$driver->vehicle->canCarryWeight($shipment->weight)) {
            return false;
        }

        // Assign the driver and update statuses
        $shipment->driver_id = $driver->id;
        $shipment->status = Shipment::STATUS_ACCEPTED;
        $shipment->save();

        // Update driver status
        $driver->status = Driver::STATUS_BUSY;
        $driver->save();

        // Notify customer that driver has been assigned
        $this->notificationService->notifyCustomerDriverAssigned($shipment);

        // Notify other drivers that the shipment is no longer available
        $this->notificationService->notifyDriversShipmentTaken($shipment);

        return true;
    }

    /**
     * Handle driver rejection of a shipment
     *
     * @param Shipment $shipment
     * @param Driver $driver
     * @return void
     */
    public function handleDriverRejection(Shipment $shipment, Driver $driver): void
    {
        // Log the rejection for analytics
        \Log::info("Driver {$driver->id} rejected shipment {$shipment->id}");

        // In a more sophisticated system, you might:
        // 1. Reduce the driver's priority for future assignments
        // 2. Expand the search radius
        // 3. Notify additional drivers
        // 4. Increase the offered price
    }

    /**
     * Cancel driver assignment (if customer cancels or other reasons)
     *
     * @param Shipment $shipment
     * @return bool
     */
    public function cancelDriverAssignment(Shipment $shipment): bool
    {
        if (!$shipment->driver_id) {
            return false;
        }

        $driver = $shipment->driver;
        
        // Update shipment status
        $shipment->driver_id = null;
        $shipment->status = Shipment::STATUS_CANCELLED;
        $shipment->save();

        // Make driver available again (if they're not on another trip)
        if ($driver && $driver->status === Driver::STATUS_BUSY) {
            $driver->status = Driver::STATUS_AVAILABLE;
            $driver->save();
        }

        // Notify driver of cancellation
        if ($driver) {
            $this->notificationService->notifyDriverShipmentCancelled($shipment, $driver);
        }

        return true;
    }

    /**
     * Get driver performance metrics for assignment prioritization
     * 
     * @param Driver $driver
     * @return array
     */
    public function getDriverMetrics(Driver $driver): array
    {
        $completedShipments = $driver->shipments()
            ->where('status', Shipment::STATUS_DELIVERED)
            ->count();

        $averageRating = $driver->ratingsReceived()
            ->avg('rating') ?? 0;

        $rejectionRate = 0; // Would be calculated from driver response logs

        return [
            'completed_shipments' => $completedShipments,
            'average_rating' => round($averageRating, 2),
            'rejection_rate' => $rejectionRate,
        ];
    }
}