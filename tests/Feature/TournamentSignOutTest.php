<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Livewire\Tournament as TournamentPage;
use App\Models\Plan;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\CreateMatches;
use App\Services\TournamentService;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

/** A player entered in a tournament that has not been drawn yet. */
function entrant(Tournament $tournament): User
{
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);
    Platform::factory()->for($user)->create([
        'platform' => $tournament->platform,
        'nickname' => 'tag'.$user->id,
    ]);

    Auth::login($user);
    app(TournamentService::class)->signUp($user, $tournament);
    Auth::logout();

    return $user;
}

it('releases the seat when a player leaves', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $leaver = entrant($tournament);
    entrant($tournament);

    Livewire::actingAs($leaver)->test(TournamentPage::class, ['tournament' => $tournament->fresh()])
        ->call('signOut')
        ->assertHasNoErrors();

    $tournament->refresh();

    expect($tournament->current_player_count)->toBe(1)
        ->and($tournament->players()->whereKey($leaver->id)->exists())->toBeFalse();
});

it('reopens a tournament that had filled up', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $leaver = entrant($tournament);
    entrant($tournament);

    expect($tournament->fresh()->status)->toBe(TournamentEnum::READY);

    Auth::login($leaver);
    app(TournamentService::class)->signOut($tournament->fresh());

    // The seat goes back on offer rather than the tournament staying full
    // with somebody in it who no longer wants to play.
    expect($tournament->fresh()->status)->toBe(TournamentEnum::PENDING)
        ->and($tournament->fresh()->current_player_count)->toBe(1);
});

it('leaves the pass untouched, so the player can enter something else', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $leaver = entrant($tournament);

    Auth::login($leaver);
    app(TournamentService::class)->signOut($tournament->fresh());

    // A subscription is only spent when a tournament finishes. Leaving one
    // they never played must not cost them the entry they paid for.
    expect($leaver->fresh()->plan()->wherePivot('status', true)->exists())->toBeTrue();
});

it('lets a player who left sign up again', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $player = entrant($tournament);

    Auth::login($player);
    app(TournamentService::class)->signOut($tournament->fresh());
    app(TournamentService::class)->signUp($player, $tournament->fresh());

    expect($tournament->fresh()->current_player_count)->toBe(1)
        ->and($tournament->players()->whereKey($player->id)->exists())->toBeTrue();
});

it('refuses to let a player leave once matches have been drawn', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $player = entrant($tournament);
    entrant($tournament);

    app(CreateMatches::class)->execute($tournament->fresh());
    $tournament->update(['status' => TournamentEnum::GAMING]);

    Livewire::actingAs($player)->test(TournamentPage::class, ['tournament' => $tournament->fresh()])
        ->call('signOut')
        ->assertHasErrors('signOut');

    expect($tournament->fresh()->current_player_count)->toBe(2)
        ->and(TournamentMatch::where('player1_id', $player->id)
            ->orWhere('player2_id', $player->id)->exists())->toBeTrue();
});

it('refuses somebody who was never in the tournament', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    entrant($tournament);

    Auth::login(User::factory()->create());

    expect(fn () => app(TournamentService::class)->signOut($tournament->fresh()))
        ->toThrow(Exception::class);

    expect($tournament->fresh()->current_player_count)->toBe(1);
});

it('offers a leave button only to a player who can still use it', function () {
    // Two entrants on a capacity of two, so the draw can actually be made.
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $player = entrant($tournament);
    entrant($tournament);

    Livewire::actingAs($player)->test(TournamentPage::class, ['tournament' => $tournament->fresh()])
        ->assertSee('Leave');

    app(CreateMatches::class)->execute($tournament->fresh());

    Livewire::actingAs($player)->test(TournamentPage::class, ['tournament' => $tournament->fresh()])
        ->assertDontSee('Leave');
});

it('takes a player back out through the API', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $player = entrant($tournament);

    Sanctum::actingAs($player);

    $this->postJson("/api/tournaments/{$tournament->id}/sign-out")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($tournament->fresh()->current_player_count)->toBe(0);
});

it('holds the API to the same rule once the draw is made', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $player = entrant($tournament);
    entrant($tournament);
    app(CreateMatches::class)->execute($tournament->fresh());

    Sanctum::actingAs($player);

    $this->postJson("/api/tournaments/{$tournament->id}/sign-out")
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($tournament->fresh()->current_player_count)->toBe(2);
});

it('refuses a guest', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);

    $this->postJson("/api/tournaments/{$tournament->id}/sign-out")->assertUnauthorized();
});
