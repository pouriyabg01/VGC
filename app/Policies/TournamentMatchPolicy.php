<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\TournamentMatch;
use Illuminate\Contracts\Auth\Authenticatable;

class TournamentMatchPolicy
{


    /**
     * Determine whether the user can create models.
     */
    public function create(Authenticatable $user): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Authenticatable $user, TournamentMatch $tournamentMatch): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Authenticatable $user, TournamentMatch $tournamentMatch): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can submit a result on a player's behalf.
     */
    public function submit(Authenticatable $user, TournamentMatch $tournamentMatch): bool
    {
        return $user instanceof Admin;
    }
}
