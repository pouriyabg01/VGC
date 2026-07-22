<?php

namespace Database\Seeders;

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TournamentUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::limit('16')->get();
        $tournament = Tournament::where('status' , TournamentEnum::PENDING)->get();
        if ($tournament->isEmpty()){
            $this->command->error('no tournament found!');
            return;
        }

        foreach ($users as $user){
            $user->tournaments()->attach(
                $tournament->first()->id ,[
            ]);
        }

        $tournament->each(function ($tour) {
            $tour->update([
                'current_player_count' => $tour->players()->count()
            ]);
        });
    }
}
