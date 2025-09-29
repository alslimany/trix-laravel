<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'weight_min',
        'weight_max',
        'pricing_multiplier',
    ];

    protected $casts = [
        'weight_min' => 'decimal:2',
        'weight_max' => 'decimal:2',
        'pricing_multiplier' => 'decimal:2',
    ];

    /**
     * Get all driver vehicles of this type.
     */
    public function driverVehicles()
    {
        return $this->hasMany(DriverVehicle::class);
    }

    /**
     * Check if weight is within this vehicle type's capacity
     */
    public function canCarryWeight(float $weight): bool
    {
        return $weight >= $this->weight_min && $weight <= $this->weight_max;
    }
}
