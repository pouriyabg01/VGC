<?php

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

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

    // A policy denial is an authorization failure, so 403 — distinct from the
    // 422 the sign-up rules return.
    $r = $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(403);
    expect($r->json('success'))->toBeFalse();
    expect($r->json('message'))->toContain('sub');
    expect($t->fresh()->players()->count())->toBe(0);
});

it('separates an authorization denial from a rule refusal', function () {
    $t = xboxTournament();

    // No subscription -> the policy stops it: 403.
    $noSub = User::factory()->create();
    Platform::factory()->for($noSub)->on(PlatformEnum::XBOX)->create();
    Sanctum::actingAs($noSub);
    $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(403);

    // Subscribed but wrong platform -> a rule stops it: 422.
    Sanctum::actingAs(playerOn(PlatformEnum::PLAYSTATION));
    $this->postJson("/api/tournaments/{$t->id}/sign-up")->assertStatus(422);

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

it('shows a denial as an inline error on the page, not a 403 screen', function () {
    $t = xboxTournament();

    // No subscription: the policy denies. The page must stay on screen with
    // the reason attached to the component, not blow up into an error page.
    $noSub = User::factory()->create();
    Platform::factory()->for($noSub)->on(PlatformEnum::XBOX)->create();

    Livewire::actingAs($noSub)
        ->test(\App\Livewire\Tournament::class, ['tournament' => $t])
        ->call('signUp')
        ->assertOk()
        ->assertHasErrors('signUp');

    expect($t->fresh()->players()->count())->toBe(0);
});
