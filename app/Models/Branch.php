<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'buffer_time',
        'photo_enabled',
        'scanner_enabled',
        'vehicle_number_required',
        'document_select_enabled',
        'free_trial_enabled',
        'iot_enabled',
    ];

    protected function casts(): array
    {
        return [
            'photo_enabled' => 'boolean',
            'scanner_enabled' => 'boolean',
            'vehicle_number_required' => 'boolean',
            'document_select_enabled' => 'boolean',
            'free_trial_enabled' => 'boolean',
            'iot_enabled' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(BranchVehicle::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function liveStat(): HasOne
    {
        return $this->hasOne(BranchLiveStat::class);
    }
}
