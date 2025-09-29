<?php

namespace App\Services;

use App\Driver;
use App\Shipment;
use App\User;

class NotificationService
{
    /**
     * Send push notification to a user's FCM token
     *
     * @param string $fcmToken
     * @param string $title
     * @param string $body
     * @param array $data Additional data payload
     * @return bool
     */
    private function sendFCMNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($fcmToken)) {
            return false;
        }

        // In a real implementation, you would use Firebase Admin SDK or HTTP API
        // For now, we'll simulate the notification sending
        
        $payload = [
            'to' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ];

        // Log the notification for debugging
        \Log::info('FCM Notification sent', $payload);

        // TODO: Implement actual FCM sending logic
        // Example using HTTP API:
        /*
        $headers = [
            'Authorization: key=' . env('FCM_SERVER_KEY'),
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
        */

        return true; // Simulated success
    }

    /**
     * Notify driver of a new shipment opportunity
     *
     * @param Driver $driver
     * @param Shipment $shipment
     * @return bool
     */
    public function notifyDriverOfNewShipment(Driver $driver, Shipment $shipment): bool
    {
        $title = 'New Shipment Available';
        $body = "Package delivery from {$shipment->origin_address} to {$shipment->destination_address}";
        
        $data = [
            'type' => 'new_shipment',
            'shipment_id' => $shipment->id,
            'estimated_price' => $shipment->final_price,
            'weight' => $shipment->weight,
            'distance' => $shipment->distance_km,
        ];

        return $this->sendFCMNotification(
            $driver->user->fcm_token,
            $title,
            $body,
            $data
        );
    }

    /**
     * Notify customer that a driver has been assigned
     *
     * @param Shipment $shipment
     * @return bool
     */
    public function notifyCustomerDriverAssigned(Shipment $shipment): bool
    {
        $title = 'Driver Assigned';
        $body = "Your package has been assigned to {$shipment->driver->user->name}";
        
        $data = [
            'type' => 'driver_assigned',
            'shipment_id' => $shipment->id,
            'driver_name' => $shipment->driver->user->name,
            'driver_phone' => $shipment->driver->user->phone,
        ];

        return $this->sendFCMNotification(
            $shipment->customer->user->fcm_token,
            $title,
            $body,
            $data
        );
    }

    /**
     * Notify customer of shipment status update
     *
     * @param Shipment $shipment
     * @param string $newStatus
     * @return bool
     */
    public function notifyCustomerStatusUpdate(Shipment $shipment, string $newStatus): bool
    {
        $statusMessages = [
            Shipment::STATUS_PICKED_UP => 'Your package has been picked up',
            Shipment::STATUS_IN_TRANSIT => 'Your package is on the way',
            Shipment::STATUS_DELIVERED => 'Your package has been delivered',
        ];

        $title = 'Shipment Update';
        $body = $statusMessages[$newStatus] ?? 'Your shipment status has been updated';
        
        $data = [
            'type' => 'status_update',
            'shipment_id' => $shipment->id,
            'status' => $newStatus,
        ];

        return $this->sendFCMNotification(
            $shipment->customer->user->fcm_token,
            $title,
            $body,
            $data
        );
    }

    /**
     * Notify driver of shipment status changes from customer
     *
     * @param Shipment $shipment
     * @param string $message
     * @return bool
     */
    public function notifyDriverFromCustomer(Shipment $shipment, string $message): bool
    {
        $title = 'Customer Update';
        $body = $message;
        
        $data = [
            'type' => 'customer_message',
            'shipment_id' => $shipment->id,
            'customer_name' => $shipment->customer->user->name,
        ];

        return $this->sendFCMNotification(
            $shipment->driver->user->fcm_token,
            $title,
            $body,
            $data
        );
    }

    /**
     * Notify drivers that a shipment has been taken by another driver
     *
     * @param Shipment $shipment
     * @return void
     */
    public function notifyDriversShipmentTaken(Shipment $shipment): void
    {
        // This would typically be sent to all drivers who were notified about this shipment
        // For now, we'll just log it
        \Log::info("Shipment {$shipment->id} has been assigned to driver {$shipment->driver_id}");
    }

    /**
     * Notify driver that a shipment has been cancelled
     *
     * @param Shipment $shipment
     * @param Driver $driver
     * @return bool
     */
    public function notifyDriverShipmentCancelled(Shipment $shipment, Driver $driver): bool
    {
        $title = 'Shipment Cancelled';
        $body = 'The shipment you were assigned has been cancelled';
        
        $data = [
            'type' => 'shipment_cancelled',
            'shipment_id' => $shipment->id,
        ];

        return $this->sendFCMNotification(
            $driver->user->fcm_token,
            $title,
            $body,
            $data
        );
    }

    /**
     * Send notification when payment is received
     *
     * @param Shipment $shipment
     * @return bool
     */
    public function notifyDriverPaymentReceived(Shipment $shipment): bool
    {
        $title = 'Payment Received';
        $body = "You've received payment of {$shipment->final_price} for delivery";
        
        $data = [
            'type' => 'payment_received',
            'shipment_id' => $shipment->id,
            'amount' => $shipment->final_price,
        ];

        return $this->sendFCMNotification(
            $shipment->driver->user->fcm_token,
            $title,
            $body,
            $data
        );
    }

    /**
     * Queue notification for delayed sending
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @param \DateTime|null $sendAt
     * @return void
     */
    public function queueNotification(User $user, string $title, string $body, array $data = [], ?\DateTime $sendAt = null): void
    {
        // In a real implementation, you would queue this job for background processing
        // For now, we'll send it immediately
        
        if ($sendAt && $sendAt > new \DateTime()) {
            // Would use Laravel Queue with delay
            \Log::info("Notification queued for {$user->id} to send at {$sendAt->format('Y-m-d H:i:s')}");
        }

        $this->sendFCMNotification($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send bulk notifications to multiple users
     *
     * @param array $users Array of User objects
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array Results of notification sending
     */
    public function sendBulkNotifications(array $users, string $title, string $body, array $data = []): array
    {
        $results = [];
        
        foreach ($users as $user) {
            $results[$user->id] = $this->sendFCMNotification(
                $user->fcm_token,
                $title,
                $body,
                $data
            );
        }

        return $results;
    }
}