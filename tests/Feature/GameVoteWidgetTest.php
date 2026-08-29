<?php

use App\Filament\Resources\Games\GameResource;
use App\Filament\Widgets\GameVoteStats;
use App\Filament\Widgets\MostWantedGames;
use App\Models\Admin;
use App\Models\Game;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(Admin::factory()->create(), 'admin'));

it('ranks the liked games on the dashboard, with what each still needs', function () {
    $loud = Game::create(['title' => 'Loud Game', 'votes_target' => 10, 'is_active' => true]);
    $quiet = Game::create(['title' => 'Quiet Game', 'votes_target' => 10, 'is_active' => true]);

    $loud->voters()->attach(User::factory()->count(4)->create()->pluck('id'));
    $quiet->voters()->attach(User::factory()->create()->id);

    $html = Livewire::test(MostWantedGames::class)->assertOk()->html();

    expect(strpos($html, 'Loud Game'))->toBeLessThan(strpos($html, 'Quiet Game'))
        ->and($html)->toContain('4 / 10')
        ->and($html)->toContain('width: 40%');
});

it('marks a game that has hit its target as ready to run', function () {
    $game = Game::create(['title' => 'Tekken 8', 'votes_target' => 3, 'is_active' => true]);
    $game->voters()->attach(User::factory()->count(3)->create()->pluck('id'));

    // The number an admin is actually waiting for, called out rather than left
    // to be worked out from two columns.
    Livewire::test(MostWantedGames::class)->assertOk()->assertSee('Ready to run');
});

it('says so plainly when nobody has liked anything', function () {
    Game::create(['title' => 'Tekken 8']);

    Livewire::test(MostWantedGames::class)->assertOk()
        ->assertSee('Nobody has liked a game yet')
        ->assertDontSee('Ready to run');
});

it('totals the likes and names the most wanted', function () {
    $top = Game::create(['title' => 'Tekken 8', 'votes_target' => 50, 'is_active' => true]);
    $top->voters()->attach(User::factory()->count(3)->create()->pluck('id'));

    Livewire::test(GameVoteStats::class)->assertOk()
        ->assertSee('Likes cast')
        ->assertSee('Tekken 8')
        ->assertSee('3 of 50 asked for');
});

it('refreshes itself, so a like shows up without the panel being reloaded', function () {
    Game::create(['title' => 'Tekken 8', 'is_active' => true]);

    // Both widgets carry the poll, and at the same cadence: two counters that
    // refresh on different clocks disagree about the same number in between.
    expect(Livewire::test(MostWantedGames::class)->html())->toContain('wire:poll.5s')
        ->and(Livewire::test(GameVoteStats::class)->html())->toContain('wire:poll.5s');
});

it('picks up a like cast while the widget is open', function () {
    $game = Game::create(['title' => 'Tekken 8', 'votes_target' => 10, 'is_active' => true]);
    $widget = Livewire::test(MostWantedGames::class)->assertSee('Nobody has liked a game yet');

    $game->voters()->attach(User::factory()->create()->id);

    // What the poll does every five seconds.
    $widget->call('$refresh')->assertSee('1 / 10');
});

it('mounts the ranking under the games table, not on the dashboard', function () {
    $game = Game::create(['title' => 'Tekken 8', 'is_active' => true]);
    $game->voters()->attach(User::factory()->create()->id);

    // It ranks the catalogue, so it belongs where the catalogue is.
    expect($this->get(GameResource::getUrl('index'))->getContent())->toContain('MostWantedGames')
        ->and($this->get('/admin')->getContent())->not->toContain('MostWantedGames');
});
