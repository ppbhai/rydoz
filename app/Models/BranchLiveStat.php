<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchLiveStat extends Model
{
    protected $fillable = [
        'branch_id',
        'online_scooters',
        'low_battery_scooters',
        'scooters',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'scooters' => 'array',
            'reported_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
