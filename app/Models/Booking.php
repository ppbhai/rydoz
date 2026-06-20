<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'otp',
        'is_verified',
        'vehicles',
        'document_type',
        'front_image',
        'back_image',
        'status',
        'discount_reason',
        'discount_amount',
        'total_amount',
        'final_amount',
        'payment_method',
        'paid_at',
        'branch',
        'branch_id',
        'branch_name',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'vehicles' => 'array',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function branchRelation(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(BookingRide::class);
    }
}
