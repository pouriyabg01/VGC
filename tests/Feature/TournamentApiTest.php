<?php

use App\Models\Admin;
use App\Models\Tournament;
use Laravel\Sanctum\Sanctum;

it('creates a tournament', function () {
    Sanctum::actingAs(Admin::factory()->create());

    $this->postJson('/api/tournaments', ['platform' => 'XBOX', 'game' => 'fifa', 'capacity' => 8])
        ->assertStatus(201);

    expect(Tournament::where('game', 'fifa')->first()->capacity)->toBe(8);
});

it('requires a capacity that is a power of two', function ($capacity, int $status) {
    Sanctum::actingAs(Admin::factory()->create());

    $payload = ['platform' => 'XBOX', 'game' => 'fifa'];
    if ($capacity !== null) {
        $payload['capacity'] = $capacity;
    }

    $this->postJson('/api/tournaments', $payload)->assertStatus($status);
})->with([
    [2, 201], [8, 201], [32, 201],
    [7, 422], [10, 422], [0, 422], [null, 422],
]);

it('validates platform on create and update', function () {
    Sanctum::actingAs(Admin::factory()->create());
    $t = Tournament::factory()->create();

    $this->postJson('/api/tournaments', ['platform' => 'Playstation', 'game' => 'x', 'capacity' => 8])
        ->assertStatus(422)->assertJsonValidationErrors('platform');

    $this->putJson("/api/tournaments/{$t->id}", ['platform' => 'XBOX', 'game' => 'nfs', 'status' => 'PENDING'])
        ->assertStatus(200);

    $this->putJson("/api/tournaments/{$t->id}", ['platform' => 'Xbox', 'game' => 'nfs', 'status' => 'PENDING'])
        ->assertStatus(422)->assertJsonValidationErrors('platform');
});

it('lists players and shows a tournament', function () {
    $t = Tournament::factory()->create();

    $this->getJson("/api/tournaments/{$t->id}")->assertStatus(200)->assertJsonPath('success', true);
    $this->getJson("/api/tournaments/{$t->id}/players")->assertStatus(200)->assertJsonPath('success', true);
});
