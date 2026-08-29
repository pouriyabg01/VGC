<?php

use App\Models\Admin;
use App\Models\Game;
use App\Models\User;

/**
 * These go through a real bearer token on purpose.
 *
 * Sanctum::actingAs() puts the user straight on the guard and never resolves a
 * token, so it passes whatever the guard's provider is set to. That is how the
 * sanctum guard sat pinned to the admins provider without a single test
 * failing, while every player calling the API got 401.
 */

/** A bearer header for a freshly minted token. */
function bearer(object $tokenable): array
{
    return ['Authorization' => 'Bearer '.$tokenable->createToken('test')->plainTextToken];
}

it('lets a player authenticate with their own token', function () {
    // subscriber() gives them an active pass, so a 200 here is the endpoint
    // answering rather than the guard turning them away.
    $player = subscriber();

    $this->getJson('/api/subscription', bearer($player))->assertOk();
});

it('lets a player vote with their own token', function () {
    $game = Game::create(['title' => 'Tekken 8', 'votes_target' => 50]);
    $player = User::factory()->create();

    $this->postJson("/api/games/{$game->id}/vote", [], bearer($player))
        ->assertOk()
        ->assertJsonPath('data.votes', 1);
});

it('lets an admin authenticate with their own token', function () {
    $admin = Admin::factory()->create();

    $this->postJson('/api/games', ['title' => 'Tekken 8'], bearer($admin))
        ->assertStatus(201);
});

it('still keeps a player out of the admin writes', function () {
    $player = User::factory()->create();
    $headers = bearer($player);

    // Authentication opening up must not open authorization up with it.
    $this->postJson('/api/games', ['title' => 'nope'], $headers)->assertForbidden();
    // A complete payload on purpose: an incomplete one is refused by
    // validation before authorization is ever reached, which proves nothing
    // about who is allowed to create a plan.
    $this->postJson('/api/plans', [
        'title' => 'nope', 'description' => 'd', 'price' => 1,
        'tournament_entries' => 1, 'vs_games' => 1,
    ], $headers)->assertForbidden();
    $this->postJson('/api/tournaments', ['game' => 'x', 'platform' => 'PC', 'capacity' => 8], $headers)->assertForbidden();

    expect(Game::count())->toBe(0);
});

it('refuses a request with no token at all', function () {
    $this->getJson('/api/subscription')->assertUnauthorized();
});
