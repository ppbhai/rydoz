<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchVehicle extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'price',
        'time',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bookingRides(): HasMany
    {
        return $this->hasMany(BookingRide::class, 'branch_vehicle_id');
    }
}
