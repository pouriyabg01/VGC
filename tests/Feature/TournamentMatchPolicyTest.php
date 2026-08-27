<?php

use App\Models\Admin;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function aMatch(): TournamentMatch
{
    $t = Tournament::factory()->create(['capacity' => 8]);

    return TournamentMatch::create([
        'tournament_id' => $t->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => 1,
    ]);
}

it('does not blow up when an admin is checked', function (string $ability) {
    $admin = Admin::factory()->create();
    $match = aMatch();

    // Passing an Admin used to raise a TypeError from the User type hint.
    expect($admin->can($ability, $match))->toBeTrue();
})->with(['update', 'delete', 'submit']);

it('allows an admin to create matches', function () {
    expect(Admin::factory()->create()->can('create', TournamentMatch::class))->toBeTrue();
});

it('denies a plain user', function (string $ability) {
    $user = User::factory()->create();

    expect($user->can($ability, aMatch()))->toBeFalse();
})->with(['update', 'delete', 'submit']);

it('lets an admin generate the bracket through the API', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $players = User::factory()->count(2)->create();
    $tournament->players()->attach($players->pluck('id'));
    $tournament->update(['current_player_count' => 2]);

    Sanctum::actingAs(Admin::factory()->create());

    $this->postJson("/api/tournaments/{$tournament->id}/matches")->assertStatus(201);
    expect($tournament->matches()->count())->toBe(1);
});

it('refuses a plain user generating the bracket', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/tournaments/{$tournament->id}/matches")->assertStatus(403);
    expect($tournament->matches()->count())->toBe(0);
});
