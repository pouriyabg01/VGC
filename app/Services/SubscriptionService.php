<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

class SubscriptionService
{
    /**
     * The user's most recent subscription, as a Plan carrying its pivot row.
     *
     * SubscriptionResource reads $this->pivot and $this->title, so a
     * subscription has to be resolved through the plan() belongsToMany. The
     * Subscription model has neither.
     */
    public function latestFor(User $user): ?Plan
    {
        return $user->plan()
            ->orderByPivot('created_at', 'desc')
            // created_at alone ties whenever two subscriptions land in the same
            // second, and the tie resolves arbitrarily — which can hand back a
            // spent subscription instead of the current one. The pivot id
            // increments per insert, so it breaks the tie in real order.
            ->orderByPivot('id', 'desc')
            ->first();
    }

    /** The latest subscription, but only while its pivot is still active. */
    public function activeFor(User $user): ?Plan
    {
        $latest = $this->latestFor($user);

        return $latest && $latest->pivot->status ? $latest : null;
    }

    /**
     * Attaches an active subscription and returns it.
     *
     * Callers check activeFor() first; this does not enforce one-at-a-time so
     * that the caller decides how to report it.
     */
    public function subscribe(User $user, Plan $plan): ?Plan
    {
        $user->plan()->attach($plan->id, ['status' => true]);

        return $this->latestFor($user);
    }

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
