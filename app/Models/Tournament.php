<?php

namespace App\Models;

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = ['platform' , 'current_player_count' , 'capacity' , 'game' , 'end_at' , 'winner_id' , 'status'];

    protected $casts = [
        'platform' => PlatformEnum::class,
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

    protected static function booted(): void
    {
        static::saving(function (Tournament $tournament) {
            // Only run sync logic if the capacity or status is being modified
            if ($tournament->isDirty(['capacity' , 'current_player_count']))  {
                $tournament->syncStatusBeforeSave();
            }
        });
    }

    /**
     * Sync status before the model is saved to the database.
     */
    public function syncStatusBeforeSave(): void
    {
        $playerCount = $this->current_player_count ?? $this->players()->count();

        // Change status to READY if count is full and status is not READY
        if ($playerCount >= $this->capacity && $this->status !== TournamentEnum::READY) {
            $this->status = TournamentEnum::READY;
        }
        // Change status to PENDING if status is READY but we now have capacity
        elseif ($playerCount < $this->capacity && $this->status === TournamentEnum::READY) {
            $this->status = TournamentEnum::PENDING;
        }
    }

}
