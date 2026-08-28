<?php

namespace App\Services;

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament;
use function PHPUnit\Framework\isNull;

class CreateMatches
{
    /**
     * Handle the incoming request.
     * @param Tournament $tournament
     * @return array ['matches' => Collection|null, 'error' => array|null]
     */
    public function execute(Tournament $tournament): array
    {
        $players = array_values($tournament->players()->pluck('users.id')->toArray());
        $count = count($players);

        $error = null;

        //check for count of player , must power of 2
        if ($count < 2 || ($count & ($count - 1)) !== 0) {
             $error = [
                'success' => false,
                'message' => 'number of players should be power of 2'
            ];
        }

        //check for existing matches
        if ($tournament->matches()->exists()) {
             $error = [
                'success' => false,
                'message' => 'this tournament already has own matches'
            ];
        }


        //check for tournament status
        if ($tournament->status === TournamentEnum::COMPLETED) {
            $error = [
                'success' => false,
                'message' => 'this tournament completed'
            ];
        }

        // The panel disables its button for anything but READY; the API had no
        // such guard, so a canceled tournament could still be started.
        if ($tournament->status === TournamentEnum::CANCELED) {
            $error = [
                'success' => false,
                'message' => 'this tournament is canceled'
            ];
        }

        shuffle($players);


        if (!$error){
            // Round one starts the clock too, otherwise the first round is the
            // only one a no-show can stall forever.
            $deadline = now()->addHours($tournament->result_deadline_hours ?: 24);

            \DB::transaction(function () use ($tournament, $players, $count, $deadline) {
                for ($i = 0; $i + 1 < $count; $i += 2) {
                    $tournament->matches()->create([
                        'player1_id' => $players[$i],
                        'player2_id' => $players[$i + 1],
                        'deadline_at' => $deadline,
                    ]);
                }
            });
        }

        return [
            'matches' => $tournament->matches()->get(),
            'error' => $error
            ];
    }
}
