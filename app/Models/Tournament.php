<?php

namespace App\Models;

use App\Enums\Tournaments\TournamentEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = ['platform' , 'current_player_count' , 'capacity' , 'game' , 'end_at' , 'winner_id' , 'status'];

    protected $casts = [
        'status' => TournamentEnum::class,
        'end_at' => 'date',
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

    public function syncStatus(): void
    {
        //change status READY if count is full and status not READY
        if ($this->current_player_count >= $this->capacity && $this->status !== TournamentEnum::READY){
            $this->update(['status' => TournamentEnum::READY]);
            //TODO fire start tournament event
        }
        //change status PENDING status READY and still has capacity
        elseif ($this->current_player_count < $this->capacity && $this->status === TournamentEnum::READY){
            $this->update(['status' => TournamentEnum::PENDING]);
        }
    }
}
