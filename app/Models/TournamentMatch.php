<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentMatchEnum;
use Illuminate\Database\Eloquent\Model;

class TournamentMatch extends Model
{
    protected $fillable = ['tournament_id','player1_id','player2_id','winner_id','player1_score','player2_score','round','deadline_at'];

    protected $casts = [
        'status' => TournamentMatchEnum::class,
        'deadline_at' => 'datetime',
        'match_date' => 'date',
    ];

    public function scopeLatestRound($query)
    {
        // Read the highest round from the query as already constrained. Taking
        // it from a fresh model query read across every tournament, so any
        // tournament on an earlier round than the deepest one matched nothing
        // and counted zero.
        $max = (clone $query)->max('round');

        return $query->where('round', $max)->get();
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
