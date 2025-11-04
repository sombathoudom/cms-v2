<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @use HasFactory<\Database\Factories\ContentRevisionFactory> */
class ContentRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'author_id',
        'revision_number',
        'body',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
