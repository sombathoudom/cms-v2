<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheEntry extends Model
{
    protected $fillable = [
        'key',
        'tags',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
