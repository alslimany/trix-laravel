<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_type_id',
        'plate_number',
        'max_weight',
    ];

    protected $casts = [
        'max_weight' => 'decimal:2',
    ];

    /**
     * Get the driver that owns this vehicle.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle type.
     */
    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Check if vehicle can carry the specified weight
     */
    public function canCarryWeight(float $weight): bool
    {
        return $weight <= $this->max_weight;
    }
}
