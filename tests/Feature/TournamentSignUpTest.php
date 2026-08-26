<?php

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function xboxTournament(array $overrides = []): Tournament
{
    return Tournament::factory()->create(array_merge([
        'platform' => PlatformEnum::XBOX,
        'capacity' => 8,
        'status' => TournamentEnum::PENDING,
    ], $overrides));
}

it('lets a subscribed player on the right platform sign up', function () {
    $t = xboxTournament();
    Sanctum::actingAs($user = playerOn(PlatformEnum::XBOX));

    $this->postJson("/api/tournaments/{$t->id}/sign-up")
        ->assertStatus(200)->assertJsonPath('success', true);

    expect($t->fresh()->players()->whereKey($user->id)->exists())->toBeTrue()
        ->and($t->fresh()->current_player_count)->toBe(1);
});

it('refuses a player whose platform does not match', function () {
    $t = xboxTournament();
    Sanctum::actingAs(playerOn(PlatformEnum::PLAYSTATION));

    $r = $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(422);
    expect($r->json('message'))->toContain('Xbox account');
    expect($t->fresh()->players()->count())->toBe(0);
});

it('refuses a player with no platform account at all', function () {
    $t = xboxTournament();
    Sanctum::actingAs(subscriber());

    $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(422);
    expect($t->fresh()->players()->count())->toBe(0);
});

it('refuses a player without an active subscription', function () {
    $t = xboxTournament();
    $user = User::factory()->create();
    Platform::factory()->for($user)->on(PlatformEnum::XBOX)->create();
    Sanctum::actingAs($user);

    // The policy denial surfaces as 422, not 403: signUp() calls authorize(),
    // and the controller's catch (\Exception) turns the AuthorizationException
    // into the same error shape as the business-rule refusals.
    $r = $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(422);
    expect($r->json('message'))->toContain('sub');
    expect($t->fresh()->players()->count())->toBe(0);
});

it('refuses the same player twice', function () {
    $t = xboxTournament();
    Sanctum::actingAs(playerOn(PlatformEnum::XBOX));

    $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(200);
    $r = $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(422);

    expect($r->json('message'))->toContain('already in this tournament');
    expect($t->fresh()->current_player_count)->toBe(1);
});

it('refuses sign-up once the tournament is no longer pending', function () {
    $t = xboxTournament(['status' => TournamentEnum::COMPLETED]);
    Sanctum::actingAs(playerOn(PlatformEnum::XBOX));

    $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(422);
    expect($t->fresh()->players()->count())->toBe(0);
});

it('flips the tournament to READY when the last seat is taken', function () {
    $t = xboxTournament(['capacity' => 2]);

    foreach (['a', 'b'] as $tag) {
        Sanctum::actingAs(playerOn(PlatformEnum::XBOX, $tag));
        $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(200);
    }

    $t->refresh();
    expect($t->current_player_count)->toBe(2)
        ->and($t->status)->toBe(TournamentEnum::READY);
});
