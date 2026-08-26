<?php

use App\Enums\Platforms\PlatformEnum;
use App\Models\Platform;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects guests', function () {
    $this->getJson('/api/platform')->assertStatus(401);
    $this->postJson('/api/platform', ['nickname' => 'x', 'platform' => 'PC'])->assertStatus(401);
});

it('creates a platform account', function () {
    Sanctum::actingAs($user = User::factory()->create());

    $this->postJson('/api/platform', ['nickname' => 'gamer', 'platform' => 'XBOX'])
        ->assertStatus(201);

    expect($user->platforms()->where('platform', PlatformEnum::XBOX)->exists())->toBeTrue();
});

it('only accepts enum values', function (string $value, int $status) {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/platform', ['nickname' => 'gamer', 'platform' => $value])
        ->assertStatus($status);
})->with([
    ['PC', 201],
    ['PLAYSTATION', 201],
    ['pc', 422],          // legacy platforms spelling
    ['Playstation', 422], // legacy tournaments spelling
    ['SWITCH', 422],
]);

it('scopes platform uniqueness per user, not globally', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/platform', ['nickname' => 'first', 'platform' => 'PC'])->assertStatus(201);

    // A second user may hold the same platform.
    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/platform', ['nickname' => 'second', 'platform' => 'PC'])->assertStatus(201);

    // The same user may not hold it twice.
    $this->postJson('/api/platform', ['nickname' => 'again', 'platform' => 'PC'])
        ->assertStatus(422)->assertJsonValidationErrors('platform');
});

it('deletes a platform', function () {
    Sanctum::actingAs($user = User::factory()->create());
    $platform = Platform::factory()->for($user)->on(PlatformEnum::PC)->create();

    $this->deleteJson("/api/platform/{$platform->id}")->assertStatus(204);

    expect(Platform::find($platform->id))->toBeNull();
});
