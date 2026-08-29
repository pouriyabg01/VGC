<?php

namespace App\Services;

use App\Models\EntryUsage;
use App\Models\Plan;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Subscriptions already resolved this request, keyed by user id.
     *
     * The header renders activeFor() on every page and the page's own
     * component usually asks again, so the same query ran twice per request.
     * Both writers below drop their user's entry, so a subscription taken out
     * or spent mid-request is still read back correctly.
     *
     * @var array<int, ?Plan>
     */
    private array $latest = [];

    /**
     * The user's most recent subscription, as a Plan carrying its pivot row.
     *
     * SubscriptionResource reads $this->pivot and $this->title, so a
     * subscription has to be resolved through the plan() belongsToMany. The
     * Subscription model has neither.
     */
    public function latestFor(User $user): ?Plan
    {
        if (array_key_exists($user->id, $this->latest)) {
            return $this->latest[$user->id];
        }

        return $this->latest[$user->id] = $user->plan()
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
        // The quota is copied off the plan, not read through to it: changing
        // a plan's numbers later must not change what somebody already paid
        // for.
        $user->plan()->attach($plan->id, [
            'status' => true,
            'tournament_entries_left' => $plan->tournament_entries,
            'vs_games_left' => $plan->vs_games,
        ]);

        $this->forget($user);

        return $this->latestFor($user);
    }

    /**
     * What is left on a user's pass.
     *
     * @return array{tournaments: int, vs_games: int}
     */
    public function remainingFor(User $user): array
    {
        $plan = $this->activeFor($user);

        return [
            'tournaments' => (int) ($plan?->pivot->tournament_entries_left ?? 0),
            'vs_games' => (int) ($plan?->pivot->vs_games_left ?? 0),
        ];
    }

    /**
     * Spends one tournament entry off the user's pass.
     *
     * Spent on the way in rather than when the tournament finishes: a player
     * who enters has taken a seat somebody else could have had, whatever
     * happens next. Returns false when there is nothing left to spend, so the
     * caller can refuse the sign-up rather than letting it through unpaid.
     */
    public function spendTournamentEntry(User $user, ?Tournament $tournament = null): bool
    {
        return $this->spend($user, EntryUsage::TOURNAMENT, 'tournament_entries_left', $tournament);
    }

    /** Spends one head-to-head off the user's pass. */
    public function spendVsGame(User $user): bool
    {
        return $this->spend($user, EntryUsage::VS_GAME, 'vs_games_left');
    }

    /**
     * Counts one off a pass, records where it went, and closes the pass once
     * nothing is left on it.
     */
    private function spend(User $user, string $type, string $column, ?Tournament $tournament = null): bool
    {
        $plan = $this->activeFor($user);

        if (! $plan || (int) $plan->pivot->{$column} < 1) {
            return false;
        }

        DB::transaction(function () use ($user, $plan, $type, $column, $tournament) {
            $left = (int) $plan->pivot->{$column} - 1;

            $other = $column === 'tournament_entries_left'
                ? (int) $plan->pivot->vs_games_left
                : (int) $plan->pivot->tournament_entries_left;

            $user->plan()->updateExistingPivot($plan->id, [
                $column => $left,
                // The pass stays usable while anything at all is left on it,
                // so a spent tournament entry does not take the VS games with
                // it.
                'status' => ($left + $other) > 0,
            ]);

            EntryUsage::create([
                'subscription_id' => $plan->pivot->id,
                'user_id' => $user->id,
                'type' => $type,
                'tournament_id' => $tournament?->id,
            ]);
        });

        $this->forget($user);

        return true;
    }

    /**
     * Closes each player's pass outright.
     *
     * No longer used when a tournament finishes — an entry is spent on the way
     * in now. Kept for an admin revoking a pass.
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

            $player->plan()->updateExistingPivot($latest->id, [
                'status' => false,
                'tournament_entries_left' => 0,
                'vs_games_left' => 0,
            ]);
            $this->forget($player);
            $deactivated++;
        }

        return $deactivated;
    }

    /** Drops a user's memoised subscription after it has been written to. */
    public function forget(User $user): void
    {
        unset($this->latest[$user->id]);
    }
}
