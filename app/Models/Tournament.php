<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = ['game' , 'end_at' , 'winner_id' , 'status'];

    public function winner()
    {
        return $this->belongsTo(User::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
    }
}
