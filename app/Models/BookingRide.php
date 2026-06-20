<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRide extends Model
{
    protected $fillable = [
        'booking_id',
        'vehicle_id',
        'branch_vehicle_id',
        'vehicle_name',
        'vehicle_price',
        'vehicle_time',
        'qty',
        'requested_minutes',
        'ride_number',
        'start_time',
        'end_time',
        'actual_minutes',
        'trip_distance_km',
        'average_speed_kph',
        'charge',
        'discount_reason',
        'discount_amount',
        'final_charge',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'charge' => 'decimal:2',
            'trip_distance_km' => 'decimal:3',
            'average_speed_kph' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_charge' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branchVehicle(): BelongsTo
    {
        return $this->belongsTo(BranchVehicle::class, 'branch_vehicle_id');
    }
}
