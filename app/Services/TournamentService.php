<?php

namespace App\Services;

use App\Enums\Tournaments\TournamentEnum;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\TournamentResource;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TournamentService extends BaseController
{
    use AuthorizesRequests;
    /**
     * Registers a user in a tournament and handles capacity logic.
     *
     * @param User $user
     * @param Tournament $tournament
     * @return Tournament
     * @throws \Exception if tournament is full or user is already registered.
     */
    public function signUp(User $user , Tournament $tournament): Tournament
    {
        $this->authorize('signUp' , $tournament);
        return DB::transaction(function () use ($user , $tournament) {
            $tournament = $tournament->findOrFail($tournament->id);
            //tournament status should PENDING
            if ($tournament->status !== TournamentEnum::PENDING){
                throw new \Exception("Tournament is not open for registration.");
            }

            //is user already sign up tournament
            if ($user->tournaments()->where('tournament_id' , $tournament->id)->exists()){
                throw  new \Exception("You are already in this tournament");
            }

            //player must own an account on the platform the tournament is played on
            if (! $user->platforms()->where('platform' , $tournament->platform)->exists()){
                throw new \Exception(
                    "You need a {$tournament->platform->label()} account to sign up for this tournament."
                );
            }

            $tournament->players()->attach($user->id);

            $affectedRow = Tournament::where('id' , $tournament->id)
                ->where('current_player_count' , '<' , $tournament->capacity)
                ->increment('current_player_count');

            if ($affectedRow === 0){
                $tournament->players()->detach($user->id);
                throw new \Exception("Tournament is full");
            }

            $tournament->refresh();
            $tournament->syncStatusBeforeSave();
            $tournament->save();

            return $tournament;
        });
    }

    /**
     * Takes a player back out of a tournament that has not started.
     *
     * Leaving is allowed right up until the tournament goes GAMING. The seat
     * is released, so one that had filled up drops back to PENDING and can
     * take somebody else.
     *
     * The player is removed completely: if a draw had already been made but
     * play had not begun, that draw is void and every match goes with them.
     * Deleting only their own match would leave their opponent holding a
     * fixture against nobody, so the whole round is cleared and drawn again
     * once the tournament is full.
     *
     * Their entry is untouched — a subscription is only spent when a
     * tournament finishes, so leaving one they never played does not cost
     * them the pass.
     *
     * @throws \Exception when play has begun, or they were never in it.
     */
    public function signOut(Tournament $tournament)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('You need an account to leave a tournament.');
        }

        // GAMING is the line: once play has begun a result may already have
        // been reported against this player, and pulling them out would
        // rewrite a round that is being played.
        if ($tournament->status !== TournamentEnum::PENDING
            && $tournament->status !== TournamentEnum::READY) {
            throw new \Exception('You cannot leave a tournament once it has started.');
        }

        DB::transaction(function () use ($tournament, $user) {
            if (! $user->tournaments()->where('tournament_id', $tournament->id)->exists()) {
                throw new \Exception('You are not registered in this tournament');
            }

            $user->tournaments()->detach($tournament->id);
            $tournament->decrement('current_player_count');

            // Nothing of them is left behind. Reports go first: match_results
            // holds a foreign key onto the match, so the matches cannot be
            // deleted while a report still points at one. The screenshots
            // those reports named are swept up by cleanup:screenshots.
            if ($tournament->matches()->exists()) {
                MatchResult::whereIn('tournament_match_id', $tournament->matches()->pluck('id'))->delete();
                $tournament->matches()->delete();
            }

            $tournament->refresh();
            $tournament->syncStatusBeforeSave();
            $tournament->save();
        });
    }
    public function finalizeTournament(Tournament $tournament , User $winner)
    {
        //Complete the Tournament
        $tournament->update([
            'winner_id' => $winner->id,
            'status'    => TournamentEnum::COMPLETED,
            'end_at'    => now(),
        ]);
    }
}
