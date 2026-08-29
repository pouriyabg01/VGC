<?php

namespace App\Filament\Widgets;

use App\Models\Game;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * What the poll on the landing page adds up to.
 *
 * A like is a player saying "put this on more". The counters are only worth
 * anything to somebody deciding what to run next, which is here.
 */
class GameVoteStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Livewire refreshes these on its own. A like landing on the landing page
    // has to show up here without anybody reloading the panel.
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $games = Game::query()->withCount('voters')->get();

        $top = $games->sortByDesc('voters_count')->first();
        $ready = $games->filter(fn (Game $game): bool => $game->voters_count >= $game->votes_target);

        return [
            Stat::make('Likes cast', $games->sum('voters_count'))
                ->description('Across '.$games->count().' '.str('game')->plural($games->count()))
                ->descriptionIcon(Heroicon::OutlinedHandThumbUp)
                // A single number says nothing about whether the poll is alive.
                // The last week beside it does.
                ->chart($this->likesPerDay())
                ->color('success'),

            Stat::make('Most wanted', $top?->voters_count ? $top->title : '—')
                ->description($top?->voters_count
                    ? $top->voters_count.' of '.$top->votes_target.' asked for'
                    : 'Nobody has liked a game yet')
                ->descriptionIcon(Heroicon::OutlinedFire)
                ->color($top?->voters_count ? 'warning' : 'gray'),

            Stat::make('At their target', $ready->count())
                ->description($ready->isNotEmpty()
                    ? $ready->pluck('title')->take(2)->join(', ').($ready->count() > 2 ? '…' : '')
                    : 'None have hit their target yet')
                ->descriptionIcon(Heroicon::OutlinedTrophy)
                ->color($ready->isNotEmpty() ? 'success' : 'gray'),
        ];
    }

    /**
     * Likes cast on each of the last seven days, oldest first.
     *
     * Read off the pivot's created_at in one grouped query, then filled in:
     * a day nobody liked anything has no row, and a sparkline that skips it
     * would draw a flat week as a busy one.
     *
     * @return array<int, int>
     */
    private function likesPerDay(): array
    {
        $counts = DB::table('game_user')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(6, 0))
            ->map(fn (int $back): int => (int) ($counts[now()->subDays($back)->toDateString()] ?? 0))
            ->all();
    }
}
