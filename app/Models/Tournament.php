<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentEnum;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = ['game' , 'end_at' , 'winner_id' , 'status'];

    protected $casts = [
        'status' => TournamentEnum::class,
    ];

    public function winner()
    {
        return $this->belongsTo(User::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function players()
    {
        return $this->belongsToMany(
            User::class ,
            'tournament_user' ,
            'tournament_id',
            'user_id'
        )->withTimestamps();
    }
}
