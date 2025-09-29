<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'rater_user_id',
        'ratee_user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the shipment this review belongs to.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the user who gave the rating.
     */
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    /**
     * Get the user who received the rating.
     */
    public function ratee()
    {
        return $this->belongsTo(User::class, 'ratee_user_id');
    }

    /**
     * Check if rating is positive (4-5 stars)
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if rating is negative (1-2 stars)
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }
}
