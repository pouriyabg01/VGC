<?php

namespace App\Services;

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TournamentService
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

            $tournament->players()->attach($user->id);

            $affectedRow = Tournament::where('id' , $tournament->id)
                ->where('current_player_count' , '<' , $tournament->capacity)
                ->increment('current_player_count');

            if ($affectedRow === 0){
                $tournament->player()->detach($tournament->id);
                throw new \Exception("Tournament is full");
            }

            $tournament->refresh();
            $tournament->syncStatus();

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
        DB::transaction(function () use ($tournament , $user){
           if ($user->tournaments()->where('tournament_id' , $tournament->id)->exists()){

               $user->tournaments()->detach($tournament->id);
               $tournament->decrement('current_player_count');

               $tournament->refresh();
               $tournament->syncStatus();
           }elseif (!$user->tournaments()->where('tournament_id' , $tournament->id)->exists()){
               throw new \Exception("You are not registered in this tournament");
           }
        });
    }
    public function finalizeTournament(Tournament $tournament , $winnerID)
    {
        //TODO winner argument should be type of user object
        //Complete the Tournament
        $tournament->update([
            'winner_id' => $winnerID,
            'status'    => TournamentEnum::COMPLETED,
            'end_at'    => now(),
        ]);
        //TODO send response and message
    }
}
