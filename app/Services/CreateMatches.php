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

        shuffle($players);


        if (!$error){
            \DB::transaction(function () use ($tournament, $players, $count) {
                for ($i = 0; $i + 1 < $count; $i += 2) {
                    $tournament->matches()->create([
                        'player1_id' => $players[$i],
                        'player2_id' => $players[$i + 1],
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
