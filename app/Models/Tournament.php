<?php

namespace App\Models;

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
        return $this->unsettledIn($this->matches()->latestRound());
    }

    /**
     * What the "Matches" stat reads on the landing, profile and tournament
     * pages.
     *
     * Three states, not two. A tournament nobody has drawn yet holds no
     * matches, so matchesLeft() answers 0 for it — and "0 left" reads as a
     * tournament that finished rather than one that has not begun.
     *
     * Lives on the model so all three pages say the same thing; the branch
     * used to be written out in each of them.
     */
    public function matchesLabel(): string
    {
        if ($this->status === TournamentEnum::COMPLETED) {
            return 'DONE!';
        }

        if ($this->status === TournamentEnum::CANCELED) {
            return 'Canceled';
        }

        $round = $this->matches()->latestRound();

        if ($round->isEmpty()) {
            return 'Not started';
        }

        return $this->unsettledIn($round).' left';
    }

    /** How many of a drawn round are still to be settled. */
    private function unsettledIn(Collection $round): int
    {
        return $round
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
