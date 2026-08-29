<?php

namespace App\Filament\Widgets;

use App\Models\Game;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * The poll as a league table.
 *
 * The games list already carries a votes column, but a column of numbers does
 * not answer the question an admin actually has — which game is close enough
 * to its target to be worth putting on. A bar against the target does.
 */
class MostWantedGames extends Widget
{
    // Carries $pollingInterval, which the view reads: a like cast on the
    // landing page shows up here without the page being reloaded.
    use CanPoll;

    // Kept off the dashboard on purpose. It is mounted under the games table
    // instead, where somebody is already looking at the catalogue it ranks.
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.most-wanted-games';

    protected int|string|array $columnSpan = 'full';

    /** How many rows read as a league table before it reads as a list. */
    private const SHOWN = 6;

    /** @return Collection<int, Game> */
    public function getGames(): Collection
    {
        return Game::query()
            ->withCount('voters')
            ->orderByDesc('voters_count')
            ->orderBy('title')
            ->take(self::SHOWN)
            ->get();
    }
}
