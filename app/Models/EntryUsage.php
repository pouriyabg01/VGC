<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One entry spent off a pass.
 *
 * The counters on a subscription say how many are left; these rows say where
 * the rest went, so a player asking why their entries are gone can be given a
 * real answer.
 */
class EntryUsage extends Model
{
    public const TOURNAMENT = 'tournament';

    public const VS_GAME = 'vs_game';

    protected $fillable = ['subscription_id', 'user_id', 'type', 'tournament_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
