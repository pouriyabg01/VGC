<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A game the site puts on.
 *
 * Nothing points at this yet — tournaments still carry their game as a plain
 * string. This is the shelf the catalogue sits on, so it can be filled and
 * shown before anything depends on it.
 */
class Game extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'image', 'votes_target', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Games the site puts on today. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Games on show but not running yet — the ones worth voting for. */
    public function scopeComingSoon($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Whether a player can vote for this game.
     *
     * Only games the site actually runs. A coming-soon card carries the button
     * greyed out rather than missing, so it reads as a thing that opens later.
     */
    public function acceptsVotes(): bool
    {
        return $this->is_active;
    }

    /**
     * Players who have asked for this game.
     *
     * The pivot carries a unique key on (game_id, user_id), so the count is
     * people rather than clicks.
     */
    public function voters()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * How far the game is towards being worth running, as a percentage.
     *
     * Capped at 100: past the target the bar is full and the raw count keeps
     * climbing beside it, rather than the bar overflowing its track.
     */
    public function votePercent(): int
    {
        $target = max(1, (int) $this->votes_target);

        return (int) min(100, round($this->voteCount() / $target * 100));
    }

    /** Reads the loaded count when there is one, so a list is not N+1. */
    public function voteCount(): int
    {
        return (int) ($this->voters_count ?? $this->voters()->count());
    }

    /**
     * Where the cover can actually be fetched from.
     *
     * Null when no image was uploaded, so a caller can fall back rather than
     * render a broken one.
     */
    public function imageUrl(): ?string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : null;
    }
}
