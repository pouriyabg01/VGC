<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->randomElement([
                'FIFA 25', 'Tekken 8', 'Mortal Kombat 1', 'Street Fighter 6',
                'NBA 2K25', 'Gran Turismo 7', 'Forza Horizon 5', 'Call of Duty',
            ]),
            'image' => null,
        ];
    }

    /** A game whose cover has been uploaded. */
    public function withImage(): static
    {
        return $this->state(fn (): array => ['image' => 'games/cover.jpg']);
    }
}
