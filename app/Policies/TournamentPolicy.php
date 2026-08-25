<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;

class TournamentPolicy
{

    /**
     * Determine whether the user can create models.
     */
    public function create(Authenticatable $user): bool
    {
        return $user instanceof \App\Models\Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Authenticatable $user , Tournament $tournament): bool
    {
        return $user instanceof \App\Models\Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Authenticatable $user, Tournament $tournament): bool
    {
        return $user instanceof \App\Models\Admin;
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
