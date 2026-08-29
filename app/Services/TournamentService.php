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

            // Spent on the way in. A seat taken is a seat somebody else
            // could have had, whatever happens in the bracket afterwards.
            if (! app(SubscriptionService::class)->spendTournamentEntry($user, $tournament)) {
                throw new \Exception('Your pass has no tournament entries left.');
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
    public function signOut(Tournament $tournament)
    {
        $user = Auth::user();
        if (! $user) return;

        // Once the draw is made the player is named in a match, and leaving
        // only drops them from the head count — their opponent is left
        // waiting on a result nobody will ever report.
        if ($tournament->status !== TournamentEnum::PENDING) {
            throw new \Exception('You cannot leave a tournament once it has started.');
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
