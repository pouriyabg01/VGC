<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Seeds the catalogue with titles only.
     *
     * No covers: an image has to be a real file on the disk, and inventing one
     * here would leave every card pointing at something that is not there.
     * Upload covers from the admin panel.
     */
    public function run(): void
    {
        $titles = [
            'FIFA 25', 'Tekken 8', 'Mortal Kombat 1', 'Street Fighter 6',
            'NBA 2K25', 'Gran Turismo 7', 'Forza Horizon 5', 'Call of Duty',
        ];

        foreach ($titles as $title) {
            Game::firstOrCreate(['title' => $title]);
        }

        $this->command?->info('GameSeeder: '.count($titles).' titles present.');
    }
}
