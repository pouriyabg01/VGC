<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = Plan::all();

        if ($plans->isEmpty()){
            $this->command->error('no plans found! seed plan first');
            return;
        }

        $userWithOutSub = User::whereDoesntHave('plan')->get();

        if ($userWithOutSub->isEmpty()){
            $this->command->error('all users have sub');
            return;
        }

        foreach ($userWithOutSub as $user){
            $user->plan()->attach($plans->random()->id, [
                'status' => 1
            ]);
        }
    }
}
