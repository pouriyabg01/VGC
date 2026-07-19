<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class Subscription
{
    public function deactive($players)
    {
        $collection = is_array($players) ? collect($players) : collect([$players]);

        if ($players instanceof Collection) {
            $collection = $players;
        }

        $collection->each(function ($player){
            if ($player instanceof User && $player->latestActiveSub){
                $player->latestActiveSub->update([
                    'status' => false
                ]);
            }
        });
    }
}
