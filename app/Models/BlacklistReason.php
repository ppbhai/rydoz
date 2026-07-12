<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistReason extends Model
{
    protected $fillable = [
        'reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
