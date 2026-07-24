<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TournamentPolicy
{

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin $user, Tournament $tournament): bool
    {
        return true;
    }

    public function signUp(User $user)
    {
        $sub = $user->latestActiveSub;
        if(!is_null($sub)){
            return Response::allow();
        }
        return Response::deny('you dont have sub');
    }

}
