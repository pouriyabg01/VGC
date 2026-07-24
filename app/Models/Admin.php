<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable implements FilamentUser
{
    protected $fillable = ['name' , 'email' , 'password' , 'remember_token'];
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
