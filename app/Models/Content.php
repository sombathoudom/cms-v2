<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/** @use HasFactory<\Database\Factories\ContentFactory> */
class Content extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected $fillable = [
        'author_id',
        'category_id',
        'featured_media_id',
        'seo_meta_id',
        'type',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'is_sticky',
        'publish_at',
        'published_at',
        'scheduled_for',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_sticky' => 'boolean',
            'publish_at' => 'datetime',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => strip_tags((string) $this->body),
            'status' => $this->status,
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function seoMeta(): BelongsTo
    {
        return $this->belongsTo(SeoMeta::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'usable', 'media_usages');
    }
}
