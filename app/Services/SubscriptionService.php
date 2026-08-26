<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class SubscriptionService
{
    /**
     * Marks each player's current subscription inactive, used when a tournament
     * finishes and the entry it paid for is spent.
     *
     * @param  iterable<User>  $players
     */
    public function deactivateFor(iterable $players): int
    {
        $deactivated = 0;

        foreach ($players as $player) {
            // plan() is the belongsToMany carrying the pivot; the Subscription
            // model has no pivot to update.
            $latest = $player->plan()
                ->wherePivot('status', true)
                ->orderByPivot('created_at', 'desc')
                ->first();

            if (! $latest) {
                continue;
            }

            $player->plan()->updateExistingPivot($latest->id, ['status' => false]);
            $deactivated++;
        }

        return $deactivated;
    }
}
