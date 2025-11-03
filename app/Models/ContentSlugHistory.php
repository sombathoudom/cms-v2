<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSlugHistory extends Model
{
    protected $fillable = [
        'content_id',
        'slug',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
