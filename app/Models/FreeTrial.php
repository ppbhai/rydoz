<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeTrial extends Model
{
    protected $fillable = [
        'branch_id',
        'branch_vehicle_id',
        'vehicle_name',
        'scooter_id',
        'assigned_at',
        'battery_percent_start',
        'battery_percent_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'battery_percent_start' => 'integer',
            'battery_percent_end' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branchVehicle(): BelongsTo
    {
        return $this->belongsTo(BranchVehicle::class);
    }
}
