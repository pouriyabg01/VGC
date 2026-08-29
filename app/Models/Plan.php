<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = ['title','description','price','tournament_entries','vs_games'];

    public function user()
    {
        return $this->belongsToMany(User::class ,
            'subscriptions' ,
            'plan_id' ,
            'user_id'
        )->withTimestamps()
            // The entry counters have to be listed here or they are simply not
            // loaded onto the pivot, and every read of them answers null.
            ->withPivot('id', 'status', 'tournament_entries_left', 'vs_games_left');
    }
}
