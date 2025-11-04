<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishQueue extends Model
{
    protected $fillable = [
        'content_id',
        'publish_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
