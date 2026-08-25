<?php

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


test('guest can see all matches of tournament', function () {
    $tournament = Tournament::factory()->create();
    User::factory()->count(3)->create();


    TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'round' => '1',
        'player1_id' => '1',
        'player2_id' => '2',
        'winner_id' => '1',
        'player1_goal' => '0',
        'player2_goal' => '0'
    ]);

    $response = $this->getJson("/api/tournaments/{$tournament->id}/matches");

    $response->assertStatus(200);
});
