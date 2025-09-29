<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    /**
     * Get the user that owns the customer profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all shipments for this customer.
     */
    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get all rating reviews given by this customer.
     */
    public function ratingsGiven()
    {
        return $this->hasMany(RatingReview::class, 'rater_user_id', 'user_id');
    }

    /**
     * Get all rating reviews received by this customer.
     */
    public function ratingsReceived()
    {
        return $this->hasMany(RatingReview::class, 'ratee_user_id', 'user_id');
    }
}
