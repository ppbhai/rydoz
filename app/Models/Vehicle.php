<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'name',
        'price',
        'time',
    ];

    public function bookingRides(): HasMany
    {
        return $this->hasMany(BookingRide::class);
    }
}
