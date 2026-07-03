<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'surface' => Post::SURFACE_GRATITUDE,
            'body' => fake()->sentence(),
            'anon' => true,
            'pseudonym' => 'Pejalan Senja TEST',
            'status' => Post::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_APPROVED,
            'published_at' => now(),
        ]);
    }

    public function held(): static
    {
        return $this->state(fn () => ['status' => Post::STATUS_HELD]);
    }
}
