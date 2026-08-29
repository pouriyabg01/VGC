<?php

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Livewire\Tournaments;
use App\Models\Tournament;
use Livewire\Livewire;

it('lists every tournament on its own page, each linked to its bracket', function () {
    $tournaments = Tournament::factory()->count(3)->create();

    $html = $this->get(route('tournaments'))->assertOk()->getContent();

    foreach ($tournaments as $tournament) {
        expect($html)->toContain(route('tournament', $tournament));
    }
});

it('narrows the list to one status', function () {
    $open = Tournament::factory()->create(['status' => TournamentEnum::PENDING, 'game' => 'open-one']);
    $done = Tournament::factory()->create(['status' => TournamentEnum::COMPLETED, 'game' => 'done-one']);

    Livewire::test(Tournaments::class)
        ->set('status', TournamentEnum::COMPLETED->value)
        ->assertSee($done->game)
        ->assertDontSee($open->game);
});

it('narrows the list to one platform', function () {
    $xbox = Tournament::factory()->create(['platform' => PlatformEnum::XBOX, 'game' => 'xbox-one']);
    $pc = Tournament::factory()->create(['platform' => PlatformEnum::PC, 'game' => 'pc-one']);

    Livewire::test(Tournaments::class)
        ->set('platform', PlatformEnum::PC->value)
        ->assertSee($pc->game)
        ->assertDontSee($xbox->game);
});

it('ignores a filter the query string made up rather than showing nothing', function () {
    $tournament = Tournament::factory()->create(['game' => 'still-here']);

    // A hand-edited or stale URL should not read as an empty board.
    Livewire::test(Tournaments::class)
        ->set('status', 'NOT-A-STATUS')
        ->assertSee($tournament->game);
});

it('splits a long board into pages, in an order that does not shuffle', function () {
    Tournament::factory()->create(['game' => 'oldest-bracket']);
    Tournament::factory()->count(14)->create(['game' => 'filler']);

    // Ordering by created_at alone ties for tournaments put on in the same
    // second, and an arbitrary order across pages repeats one and hides another.
    Livewire::test(Tournaments::class)
        ->assertSee('15 tournaments')
        ->assertSee('Page 1 of 2')
        ->assertDontSee('oldest-bracket')
        ->call('nextPage')
        ->assertSee('oldest-bracket');
});

it('says a filter matched nothing rather than looking like an empty board', function () {
    Tournament::factory()->create(['status' => TournamentEnum::PENDING]);

    Livewire::test(Tournaments::class)
        ->set('status', TournamentEnum::COMPLETED->value)
        ->assertSee('Nothing matches those filters');
});

it('serves the tournament list over the API too', function () {
    Tournament::factory()->create(['platform' => PlatformEnum::XBOX, 'game' => 'xbox-one']);
    Tournament::factory()->create(['platform' => PlatformEnum::PC, 'game' => 'pc-one']);

    $this->getJson('/api/tournaments')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/tournaments?platform='.PlatformEnum::PC->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.game', 'pc-one');
});

it('refuses a filter the API does not know', function () {
    $this->getJson('/api/tournaments?status=NOT-A-STATUS')
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});
