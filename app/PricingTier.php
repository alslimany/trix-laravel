<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'min_km',
        'max_km',
        'base_price',
    ];

    protected $casts = [
        'min_km' => 'decimal:2',
        'max_km' => 'decimal:2',
        'base_price' => 'decimal:2',
    ];

    /**
     * Get the pricing zone that owns this tier.
     */
    public function pricingZone()
    {
        return $this->belongsTo(PricingZone::class, 'zone_id');
    }

    /**
     * Check if a distance falls within this tier
     */
    public function includesDistance(float $distance): bool
    {
        return $distance >= $this->min_km && $distance <= $this->max_km;
    }
}
