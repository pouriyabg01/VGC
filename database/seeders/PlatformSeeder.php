<?php

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

/**
 * Gives every player who signed up for a tournament an account on that
 * tournament's platform, so the player list on a tournament page resolves to a
 * real nickname instead of a blank.
 *
 * Runs after TournamentUser, which is what creates the sign-ups.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $tournaments = Tournament::with('players')->get();

        if ($tournaments->isEmpty()) {
            $this->command->error('no tournament found!');
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($tournaments as $tournament) {
            foreach ($tournament->players as $player) {
                // platforms is unique on (platform, user_id): a player already
                // registered on this platform keeps the nickname they have.
                $exists = Platform::where('user_id', $player->id)
                    ->where('platform', $tournament->platform)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Platform::factory()
                    ->for($player)
                    ->on($tournament->platform)
                    ->create();

                $created++;
            }
        }

        $this->command->info("PlatformSeeder: {$created} created, {$skipped} already present.");
    }
}
