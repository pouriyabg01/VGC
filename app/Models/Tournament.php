<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = ['game' , 'end_at' , 'winner_id' , 'status'];

    protected $casts = [
        'status' => TournamentEnum::class,
    ];

    protected $attributes = [
        'status' => TournamentEnum::PENDING->value
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
