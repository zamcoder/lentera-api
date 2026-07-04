<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name_enc' => random_bytes(32),   // ciphertext dummy (E2E)
            'name_nonce' => random_bytes(12),
            'avatar_color' => 'mint',
        ];
    }
}
