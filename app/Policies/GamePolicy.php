<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Game;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The catalogue is public to read and admin-only to change.
 */
class GamePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Game $game): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return $user instanceof Admin;
    }

    public function update(Authenticatable $user, Game $game): bool
    {
        return $user instanceof Admin;
    }

    public function delete(Authenticatable $user, Game $game): bool
    {
        return $user instanceof Admin;
    }
}
