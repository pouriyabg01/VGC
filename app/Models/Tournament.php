<?php

namespace App\Models;

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = ['platform' , 'current_player_count' , 'capacity' , 'game' , 'end_at' , 'winner_id' , 'status' , 'result_deadline_hours'];

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

    /**
     * How many matches of the current round are still to be settled.
     *
     * Counts what is left rather than what exists: a round of 8 with one match
     * already played still read 8, so the card looked like nothing had happened
     * all round. A DISPUTED match counts as left, because it has no result
     * until an admin judges it.
     */
    public function matchesLeft(): int
    {
        return $this->matches()
            ->latestRound()
            ->reject(fn (TournamentMatch $match): bool => $match->status === TournamentMatchEnum::COMPLETED)
            ->count();
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
