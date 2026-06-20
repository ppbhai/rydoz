<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'scanner',
        'photo',
        'buffer_time',
    ];

    protected function casts(): array
    {
        return [
            'scanner' => 'boolean',
            'photo' => 'boolean',
            'buffer_time' => 'integer',
        ];
    }
}
