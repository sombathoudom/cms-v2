<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Content */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->relationLoaded('category') ? $this->category : null;
        $featuredMedia = $this->relationLoaded('featuredMedia') ? $this->featuredMedia : null;

        return [
            'type' => 'posts',
            'id' => (string) $this->id,
            'attributes' => [
                'title' => $this->title,
                'slug' => $this->slug,
                'excerpt' => $this->excerpt,
                'body' => $this->body,
                'published_at' => optional($this->published_at)?->toIso8601String(),
                'is_sticky' => (bool) $this->is_sticky,
            ],
            'relationships' => [
                'author' => [
                    'data' => $this->relationLoaded('author') && $this->author ? [
                        'type' => 'users',
                        'id' => (string) $this->author->id,
                    ] : null,
                ],
                'category' => [
                    'data' => $category ? [
                        'type' => 'categories',
                        'id' => (string) $category->id,
                    ] : null,
                    'meta' => $category ? [
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ] : null,
                ],
                'tags' => [
                    'data' => $this->whenLoaded('tags', function () {
                        return $this->tags->map(static fn ($tag) => [
                            'type' => 'tags',
                            'id' => (string) $tag->id,
                        ])->values()->all();
                    }, []),
                    'meta' => $this->whenLoaded('tags', function () {
                        return $this->tags->map(static fn ($tag) => [
                            'id' => $tag->id,
                            'name' => $tag->name,
                            'slug' => $tag->slug,
                        ])->values()->all();
                    }, []),
                ],
                'featured_media' => [
                    'data' => $featuredMedia ? [
                        'type' => 'media',
                        'id' => (string) $featuredMedia->id,
                    ] : null,
                    'meta' => $featuredMedia ? [
                        'filename' => $featuredMedia->filename,
                        'disk' => $featuredMedia->disk,
                    ] : null,
                ],
            ],
            'links' => [
                'self' => route('api.v1.posts.show', ['slug' => $this->slug]),
            ],
        ];
    }
}
