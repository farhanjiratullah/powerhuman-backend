<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => $this->faker->randomElement(Team::pluck('id')),
            'role_id' => $this->faker->randomElement(Role::pluck('id')),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->freeEmail(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'age' => $this->faker->numberBetween(18, 60),
            'phone' => $this->faker->numerify('############'),
            'photo' => $this->faker->imageUrl(),
            'verified_at' => now(),
        ];
    }
}
