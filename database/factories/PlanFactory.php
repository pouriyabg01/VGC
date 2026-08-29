<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->word(),
            'description' => fake()->text(60),
            'price' => fake()->numberBetween(100_000, 550_000),
            'tournament_entries' => fake()->numberBetween(1, 5),
            'vs_games' => fake()->numberBetween(2, 10),
        ];
    }
}
