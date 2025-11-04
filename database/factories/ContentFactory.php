<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\SeoMeta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'featured_media_id' => Media::factory(),
            'seo_meta_id' => SeoMeta::factory(),
            'type' => fake()->randomElement(['post', 'page']),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'excerpt' => fake()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'review', 'published']),
            'is_sticky' => fake()->boolean(10),
            'publish_at' => now()->addDays(1),
            'published_at' => null,
            'scheduled_for' => null,
            'meta' => ['reading_time' => fake()->numberBetween(1, 10)],
        ];
    }

    public function published(): self
    {
        return $this->state(function () {
            $publishedAt = now()->subDays(fake()->numberBetween(0, 10));

            return [
                'status' => 'published',
                'publish_at' => $publishedAt,
                'published_at' => $publishedAt,
            ];
        });
    }

    public function draft(): self
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'publish_at' => null,
            'published_at' => null,
        ]);
    }

    public function post(): self
    {
        return $this->state(fn () => ['type' => 'post']);
    }

    public function page(): self
    {
        return $this->state(fn () => ['type' => 'page']);
    }
}
