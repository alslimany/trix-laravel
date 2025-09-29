<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'lng',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];

    /**
     * Get all pricing zones for this city.
     */
    public function pricingZones()
    {
        return $this->hasMany(PricingZone::class);
    }

    /**
     * Get internal pricing zone for this city.
     */
    public function internalPricingZone()
    {
        return $this->hasOne(PricingZone::class)->where('zone_type', PricingZone::TYPE_INTERNAL);
    }

    /**
     * Get external pricing zone for this city.
     */
    public function externalPricingZone()
    {
        return $this->hasOne(PricingZone::class)->where('zone_type', PricingZone::TYPE_EXTERNAL);
    }
}
