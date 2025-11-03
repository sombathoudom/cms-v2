<?php

namespace Database\Factories;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    protected $model = SeoMeta::class;

    public function definition(): array
    {
        return [
            'meta_title' => fake()->sentence(6),
            'meta_description' => fake()->sentence(15),
            'canonical_url' => fake()->url(),
            'open_graph' => [
                'title' => fake()->sentence(),
                'description' => fake()->sentence(10),
                'image' => fake()->imageUrl(),
            ],
        ];
    }
}
