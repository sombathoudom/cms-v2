<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapEntry extends Model
{
    protected $fillable = [
        'url',
        'change_frequency',
        'priority',
        'last_modified_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'float',
            'last_modified_at' => 'datetime',
        ];
    }
}
