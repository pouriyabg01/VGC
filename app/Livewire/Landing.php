<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Plan;
use App\Models\Tournament;
use App\Services\SubscriptionService;
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

        // A game that is not on yet has its button greyed out. Refused here
        // too, since a disabled button can be forged.
        if (! $game || ! $game->acceptsVotes()) {
            return;
        }

        // toggle() adds the vote or takes it back, and the pivot's unique key
        // means a double click cannot count twice.
        $game->voters()->toggle($user->id);
    }

    /**
     * The pass the viewer already holds, if any.
     *
     * Same source as the plans page, so a card cannot offer a purchase in one
     * place that the other says is already held. SubscriptionService is scoped
     * to the request, so asking once per card still costs one query.
     */
    public function activeSubscription(): ?Plan
    {
        $user = Auth::user();

        return $user ? app(SubscriptionService::class)->activeFor($user) : null;
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

        // Running first, then the rest. One grid, two states, rather than two
        // grids of the same cards.
        $live = $games->where('is_active', true)->values();

        return view('livewire.landing', [
            'games' => $live->concat($games->where('is_active', false)->sortBy('title')->values()),
            // The three the most players have asked for. Only games that can
            // be voted for: a ranking nobody can move is not a ranking.
            'mostWanted' => $live->take(3),
            'plans' => Plan::query()
                ->latest()
                ->get(),
            // A preview, not the catalogue. The full list lives on the
            // tournaments page, which the section links to.
            'tournaments' => Tournament::query()
                ->withCount(['players', 'matches'])
                ->with('winner')
                ->latest()
                ->orderByDesc('id')
                ->take(6)
                ->get(),
        ])->layout('components.layouts.app');
    }
}
