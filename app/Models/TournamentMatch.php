<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentMatchEnum;
use Illuminate\Database\Eloquent\Model;

class TournamentMatch extends Model
{
    protected $fillable = ['tournament_id','player1_id','player2_id','winner_id','player1_goal','player2_goal','round'];

    protected $casts = [
        'status' => TournamentMatchEnum::class,
    ];

    public function scopeLatestRound($query)
    {
        $max = $this->newQuery()->max('round');
        return $query->where('round' , $max)->get();
    }
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1()
    {
        return $this->belongsTo(User::class);
    }

    public function player2()
    {
        return $this->belongsTo(User::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(MatchResult::class);
    }
}
