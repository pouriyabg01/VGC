<?php

namespace App\Services;

use App\Enums\Tournaments\TournamentEnum;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\TournamentResource;
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
     * Sign out user of tournament
     */
    /**
     * Takes a player back out of a tournament they have not yet played.
     *
     * The seat is released, so a tournament that had filled up drops back to
     * PENDING and can take somebody else. The entry they hold is untouched:
     * a subscription is only spent when a tournament finishes, so leaving one
     * they never played does not cost them the pass.
     *
     * @throws \Exception when the draw has been made, or they were never in it.
     */
    public function signOut(Tournament $tournament)
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('You need an account to leave a tournament.');
        }

        // Leaving is only safe before the draw. Once matches exist the player
        // is named in one, and dropping them leaves their opponent waiting on
        // a result nobody will ever report. A tournament that is merely full
        // has not been drawn yet, so it is still safe to leave.
        if ($tournament->matches()->exists()
            || ! in_array($tournament->status, [TournamentEnum::PENDING, TournamentEnum::READY], true)) {
            throw new \Exception('You cannot leave a tournament once the draw has been made.');
        }
        DB::transaction(function () use ($tournament , $user){
           if ($user->tournaments()->where('tournament_id' , $tournament->id)->exists()){

               $user->tournaments()->detach($tournament->id);
               $tournament->decrement('current_player_count');

               $tournament->refresh();
               $tournament->syncStatusBeforeSave();
               $tournament->save();
           }elseif (!$user->tournaments()->where('tournament_id' , $tournament->id)->exists()){
               throw new \Exception("You are not registered in this tournament");
           }
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
