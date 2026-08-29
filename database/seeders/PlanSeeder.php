<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * The three passes the site sells.
     *
     * Named rather than generated: these are the real product, and a random
     * factory plan made the plans page and checkout impossible to read.
     */
    public function run(): void
    {
        $plans = [
            [
                'title' => 'Silver',
                'description' => 'One bracket and a couple of head-to-heads. Enough to find out whether you like it.',
                'price' => 100_000,
                'tournament_entries' => 1,
                'vs_games' => 2,
            ],
            [
                'title' => 'Gold',
                'description' => 'Two brackets and five head-to-heads. For players who are here most weeks.',
                'price' => 250_000,
                'tournament_entries' => 2,
                'vs_games' => 5,
            ],
            [
                'title' => 'Platinum',
                'description' => 'Five brackets and ten head-to-heads. For players chasing the leaderboard.',
                'price' => 550_000,
                'tournament_entries' => 5,
                'vs_games' => 10,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['title' => $plan['title']], $plan);
        }

        $this->command?->info('PlanSeeder: '.count($plans).' passes present.');
    }
}
