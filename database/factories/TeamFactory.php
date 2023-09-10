<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activatedAt = [null, fake()->dateTimeBetween('-1 month', 'now')];

        return [
            'name' => fake()->catchPhrase(),
            'icon' => fake()->imageUrl(70, 70),
            'activated_at' => fake()->randomElement($activatedAt),
        ];
    }
}
