<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'zone_type',
        'name',
    ];

    /**
     * Zone type constants
     */
    const TYPE_INTERNAL = 'I';
    const TYPE_EXTERNAL = 'E';

    /**
     * Get the city that owns this pricing zone.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get all pricing tiers for this zone.
     */
    public function pricingTiers()
    {
        return $this->hasMany(PricingTier::class, 'zone_id');
    }

    /**
     * Check if this is an internal zone
     */
    public function isInternal(): bool
    {
        return $this->zone_type === self::TYPE_INTERNAL;
    }

    /**
     * Check if this is an external zone
     */
    public function isExternal(): bool
    {
        return $this->zone_type === self::TYPE_EXTERNAL;
    }
}
