<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'driver_id',
        'origin_lat',
        'origin_lng',
        'origin_address',
        'destination_lat',
        'destination_lng',
        'destination_address',
        'status',
        'final_price',
        'weight',
        'distance_km',
    ];

    protected $casts = [
        'origin_lat' => 'decimal:8',
        'origin_lng' => 'decimal:8',
        'destination_lat' => 'decimal:8',
        'destination_lng' => 'decimal:8',
        'final_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'distance_km' => 'decimal:2',
    ];

    /**
     * Shipment status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the customer that owns this shipment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the driver assigned to this shipment.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get all rating reviews for this shipment.
     */
    public function ratingReviews()
    {
        return $this->hasMany(RatingReview::class);
    }

    /**
     * Check if shipment is pending driver assignment
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if shipment has been completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if shipment is in progress
     */
    public function inProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT
        ]);
    }
}
