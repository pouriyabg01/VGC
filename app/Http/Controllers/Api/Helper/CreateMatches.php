<?php

namespace App\Http\Controllers\Api\Helper;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tournament;

class CreateMatches extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(array $request)
    {
            $players = $request['players'];
            $count = count($players);

            if (($count & ($count - 1)) !== 0) {
                return $this->sendError([], 'تعداد بازیکنان باید مضارب ۲ باشد', 422);
            }

            shuffle($players);

            $tournament = Tournament::findOrFail($request['tournament_id']);
            if ($tournament->matches()->exists()) {
                return $this->sendError([], 'this tournament has own matches');
            }

            for ($i = 0; $i < $count; $i += 2) {
                $tournament->matches()->create([
                    'player1' => $players[$i],
                    'player2' => $players[$i + 1],
                ]);
            }

            return $tournament->matches;
        }
}
