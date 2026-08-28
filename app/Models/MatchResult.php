<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentMatchResultEnum;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    protected $fillable = ['tournament_match_id' , 'user_id' , 'screenshot' ,'scored' , 'conceded' , 'status'];

    protected static function booted()
    {
        static::creating(function ($model){
            if (auth()->check()){
                $model->user_id = auth()->id();
            }
        });
    }

    protected $casts = [
        'status' => TournamentMatchResultEnum::class,
    ];

    public function tournamentMatch()
    {
        return $this->belongsTo(TournamentMatch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
