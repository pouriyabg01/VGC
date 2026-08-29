<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Plan;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Landing extends Component
{
    /**
     * Adds or takes back the viewer's vote for a game.
     *
     * A vote is a person, not a click: the pivot is unique on (game, user), so
     * pressing twice takes the vote back rather than counting again.
     */
    public function toggleVote(int $gameId): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $game = Game::find($gameId);

        if (! $game) {
            return;
        }

        // toggle() adds the vote or takes it back, and the pivot's unique key
        // means a double click cannot count twice.
        $game->voters()->toggle($user->id);
    }

    public function render()
    {
        $userId = Auth::id();

        $games = Game::query()
            ->withCount('voters')
            // Whether *this* viewer has voted, resolved in the same query
            // rather than once per card.
            ->when($userId, fn ($query) => $query->withExists([
                'voters as voted_by_viewer' => fn ($q) => $q->whereKey($userId),
            ]))
            ->orderByDesc('voters_count')
            ->orderBy('title')
            ->get();

        return view('livewire.landing', [
            'games' => $games,
            // The hero's bars. Three is enough to read at a glance, and they
            // are the same records the section below votes on.
            'mostWanted' => $games->take(3),
            'plans' => Plan::query()
                ->latest()
                ->get(),
            'tournaments' => Tournament::query()
                ->withCount(['players', 'matches'])
                ->with('winner')
                ->latest()
                ->get(),
        ])->layout('components.layouts.app');
    }
}
