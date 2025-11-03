<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRevision>
 */
class ContentRevisionFactory extends Factory
{
    protected $model = ContentRevision::class;

    public function definition(): array
    {
        return [
            'content_id' => Content::factory(),
            'author_id' => User::factory(),
            'revision_number' => fake()->unique()->numberBetween(1, 10),
            'body' => fake()->paragraphs(2, true),
            'meta' => ['note' => fake()->sentence()],
        ];
    }
}
