<?php

namespace App\Http\Controllers\Api\Helper;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tournament;
use function PHPUnit\Framework\isNull;

class CreateMatches extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Tournament $tournament): array
    {
        $players = array_values($tournament->players()->pluck('users.id')->toArray());
        $count = count($players);

        $error = null;
        if ($count < 2 || ($count & ($count - 1)) !== 0) {
             $error = [
                'success' => false,
                'message' => 'players should be power of 2'
            ];
        }

        if ($tournament->matches()->exists()) {
             $error = [
                'success' => false,
                'message' => 'this tournament has own matches'
            ];
        }

        //TODO make error message for completed tournament

        shuffle($players);


        if (isNull($error)){
            for ($i = 0; $i+1 < $count; $i += 2) {
                $tournament->matches()->create([
                    'player1' => $players[$i],
                    'player2' => $players[$i + 1],
                ]);
            }
        }
        
        return [
            'matches' => $tournament->matches,
            'error' => $error
            ];
    }
}
