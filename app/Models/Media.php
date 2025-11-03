<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/** @use HasFactory<\Database\Factories\MediaFactory> */
class Media extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected $fillable = [
        'uuid',
        'disk',
        'directory',
        'filename',
        'extension',
        'mime_type',
        'size',
        'width',
        'height',
        'checksum',
        'alt_text',
        'uploaded_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'alt_text' => $this->alt_text,
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function contents(): MorphToMany
    {
        return $this->morphedByMany(Content::class, 'usable', 'media_usages');
    }

    protected static function booted(): void
    {
        static::creating(function (self $media): void {
            if (! $media->uuid) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }
}
