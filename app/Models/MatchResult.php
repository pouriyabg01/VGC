<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentMatchResultEnum;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    protected $fillable = ['tournament_match_id' , 'user_id' , 'scored_goals' , 'conceded_goals' , 'status'];

    protected $casts = [
        'status' => TournamentMatchResultEnum::class,
    ];

    public function tournamentMatch()
    {
        return $this->belongsTo(TournamentMatch::class);
    }
}
