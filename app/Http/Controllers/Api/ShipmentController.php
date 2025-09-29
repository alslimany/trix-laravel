<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shipment;
use App\Services\PricingService;
use App\Services\DriverAssignmentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShipmentController extends Controller
{
    protected $pricingService;
    protected $driverAssignmentService;
    protected $notificationService;

    public function __construct(
        PricingService $pricingService,
        DriverAssignmentService $driverAssignmentService,
        NotificationService $notificationService
    ) {
        $this->pricingService = $pricingService;
        $this->driverAssignmentService = $driverAssignmentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get price quote for a shipment
     */
    public function getPriceQuote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'weight' => 'required|numeric|min:0.1',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Calculate distance
            $distance = $this->pricingService->calculateDistanceBetweenCoordinates(
                $request->origin_lat,
                $request->origin_lng,
                $request->destination_lat,
                $request->destination_lng
            );

            // Determine if internal or external shipping
            $isInternal = $this->pricingService->isInternalShipping(
                $request->origin_lat,
                $request->origin_lng,
                $request->destination_lat,
                $request->destination_lng
            );

            // Calculate price
            $price = $this->pricingService->calculatePrice(
                $request->origin_lat,
                $request->origin_lng,
                $distance,
                $request->vehicle_type_id,
                $isInternal
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'estimated_price' => $price,
                    'distance_km' => round($distance, 2),
                    'is_internal' => $isInternal,
                    'shipping_type' => $isInternal ? 'Internal' : 'External'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate price',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create a new shipment (Customer only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only customers can create shipments'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'origin_address' => 'required|string|max:500',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'destination_address' => 'required|string|max:500',
            'weight' => 'required|numeric|min:0.1',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Calculate distance and price
            $distance = $this->pricingService->calculateDistanceBetweenCoordinates(
                $request->origin_lat,
                $request->origin_lng,
                $request->destination_lat,
                $request->destination_lng
            );

            $isInternal = $this->pricingService->isInternalShipping(
                $request->origin_lat,
                $request->origin_lng,
                $request->destination_lat,
                $request->destination_lng
            );

            $price = $this->pricingService->calculatePrice(
                $request->origin_lat,
                $request->origin_lng,
                $distance,
                $request->vehicle_type_id,
                $isInternal
            );

            // Create shipment
            $shipment = Shipment::create([
                'customer_id' => $request->user()->customer->id,
                'origin_lat' => $request->origin_lat,
                'origin_lng' => $request->origin_lng,
                'origin_address' => $request->origin_address,
                'destination_lat' => $request->destination_lat,
                'destination_lng' => $request->destination_lng,
                'destination_address' => $request->destination_address,
                'weight' => $request->weight,
                'distance_km' => $distance,
                'final_price' => $price,
                'status' => Shipment::STATUS_PENDING,
            ]);

            // Find and notify drivers
            $notifiedDrivers = $this->driverAssignmentService->findAndNotifyDrivers($shipment);

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully',
                'data' => [
                    'shipment' => $shipment,
                    'notified_drivers_count' => count($notifiedDrivers)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's shipments
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Shipment::with(['customer.user', 'driver.user']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->customer->id);
        } elseif ($user->isDriver()) {
            $query->where('driver_id', $user->driver->id);
        } else {
            // Admin sees all shipments
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $shipments
        ]);
    }

    /**
     * Get specific shipment details
     */
    public function show(Request $request, Shipment $shipment)
    {
        $user = $request->user();

        // Check if user has permission to view this shipment
        if ($user->isCustomer() && $shipment->customer_id !== $user->customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($user->isDriver() && $shipment->driver_id !== $user->driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $shipment->load(['customer.user', 'driver.user', 'ratingReviews']);

        return response()->json([
            'success' => true,
            'data' => $shipment
        ]);
    }

    /**
     * Driver accepts a shipment
     */
    public function acceptShipment(Request $request, Shipment $shipment)
    {
        if (!$request->user()->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can accept shipments'
            ], 403);
        }

        $driver = $request->user()->driver;

        $success = $this->driverAssignmentService->assignDriverToShipment($shipment, $driver);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to accept shipment. It may have been taken by another driver or you may not be available.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment accepted successfully',
            'data' => $shipment->fresh(['customer.user'])
        ]);
    }

    /**
     * Update shipment status
     */
    public function updateStatus(Request $request, Shipment $shipment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:picked_up,in_transit,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Only assigned driver can update status
        if (!$user->isDriver() || $shipment->driver_id !== $user->driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the assigned driver can update shipment status'
            ], 403);
        }

        try {
            $shipment->status = $request->status;
            $shipment->save();

            // Notify customer of status change
            $this->notificationService->notifyCustomerStatusUpdate($shipment, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Shipment status updated successfully',
                'data' => $shipment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, Shipment $shipment)
    {
        $user = $request->user();

        // Only customer who created the shipment can cancel it
        if (!$user->isCustomer() || $shipment->customer_id !== $user->customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the customer who created the shipment can cancel it'
            ], 403);
        }

        // Can only cancel if not yet delivered
        if ($shipment->status === Shipment::STATUS_DELIVERED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a delivered shipment'
            ], 400);
        }

        try {
            $this->driverAssignmentService->cancelDriverAssignment($shipment);

            return response()->json([
                'success' => true,
                'message' => 'Shipment cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
