<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_verified',
        'license_photo',
        'status',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Driver status constants
     */
    const STATUS_OFFLINE = 'offline';
    const STATUS_AVAILABLE = 'available';
    const STATUS_BUSY = 'busy';
    const STATUS_ON_TRIP = 'on_trip';

    /**
     * Get the user that owns the driver profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver's vehicle.
     */
    public function vehicle()
    {
        return $this->hasOne(DriverVehicle::class);
    }

    /**
     * Get all shipments assigned to this driver.
     */
    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get all rating reviews given by this driver.
     */
    public function ratingsGiven()
    {
        return $this->hasMany(RatingReview::class, 'rater_user_id', 'user_id');
    }

    /**
     * Get all rating reviews received by this driver.
     */
    public function ratingsReceived()
    {
        return $this->hasMany(RatingReview::class, 'ratee_user_id', 'user_id');
    }

    /**
     * Check if driver is available for new assignments
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_verified;
    }

    /**
     * Check if driver is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }
}
