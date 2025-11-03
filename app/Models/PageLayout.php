<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLayout extends Model
{
    protected $fillable = [
        'name',
        'template',
        'schema',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
        ];
    }
}
