<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $filename = fake()->unique()->lexify('image????');

        return [
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'directory' => 'uploads/' . fake()->word(),
            'filename' => $filename,
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'width' => fake()->numberBetween(300, 1920),
            'height' => fake()->numberBetween(300, 1080),
            'checksum' => Str::random(40),
            'alt_text' => fake()->sentence(),
            'uploaded_by' => User::factory(),
            'meta' => ['source' => 'factory'],
        ];
    }
}
