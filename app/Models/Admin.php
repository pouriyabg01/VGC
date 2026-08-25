<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable implements FilamentUser
{
    use HasFactory,HasApiTokens;
    protected $fillable = ['name' , 'email' , 'password'];

    protected $hidden = ['password' , 'remember_token'];
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
