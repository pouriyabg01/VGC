<?php

namespace Database\Factories;

use App\Enums\Tournaments\TournamentEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game' => fake()->randomElement(['fifa' , 'nfs' , 'mortal' , 'crash' , 'GT7' , 'PES']),
            'platform' => 'PC',
            'capacity' => '32',
            'end_at' => null,
            'winner_id' => null,
//            'status' => TournamentEnum::PENDING //default value is pending
        ];
    }
}
