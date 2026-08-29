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
        // Two are running; the rest are on show as coming soon, which is what
        // the poll on the landing page is for.
        $titles = [
            'FIFA 25' => true,
            'Mortal Kombat 1' => true,
            'Tekken 8' => false,
            'Street Fighter 6' => false,
            'NBA 2K25' => false,
            'Gran Turismo 7' => false,
            'Forza Horizon 6' => false,
            'Call of Duty' => false,
        ];

        foreach ($titles as $title => $isActive) {
            // Only the flag is written on an existing row, so a cover uploaded
            // from the panel survives a re-seed.
            Game::updateOrCreate(['title' => $title], ['is_active' => $isActive]);
        }

        $this->command?->info('GameSeeder: '.count($titles).' titles present.');
    }
}
