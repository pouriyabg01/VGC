<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Livewire\Tournament as TournamentPage;
use App\Models\MatchResult;
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

/** A player entered in a tournament that has not started. */
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

    expect($tournament->fresh()->status)->toBe(TournamentEnum::PENDING)
        ->and($tournament->fresh()->current_player_count)->toBe(1);
});

it('leaves nothing of the player behind when a draw had been made', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $leaver = entrant($tournament);
    $other = entrant($tournament);

    app(CreateMatches::class)->execute($tournament->fresh());
    $match = $tournament->matches()->sole();

    MatchResult::withoutEvents(fn () => MatchResult::create([
        'tournament_match_id' => $match->id,
        'user_id' => $leaver->id,
        'screenshot' => 'conclusion-screenshot/x.jpg',
        'scored' => 2,
        'conceded' => 1,
    ]));

    Auth::login($leaver);
    app(TournamentService::class)->signOut($tournament->fresh());

    // The draw is void: keeping the leaver's own match would hand their
    // opponent a fixture against nobody, so the round is cleared and drawn
    // again once the tournament fills up.
    expect($tournament->fresh()->matches()->count())->toBe(0)
        ->and(MatchResult::count())->toBe(0)
        ->and($tournament->players()->whereKey($leaver->id)->exists())->toBeFalse()
        ->and($tournament->players()->whereKey($other->id)->exists())->toBeTrue()
        ->and($tournament->fresh()->current_player_count)->toBe(1);
});

it('leaves the pass untouched, so the player can enter something else', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $leaver = entrant($tournament);

    Auth::login($leaver);
    app(TournamentService::class)->signOut($tournament->fresh());

    // A subscription is only spent when a tournament finishes.
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

it('refuses to let a player leave once play has begun', function () {
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

it('offers a leave button until play begins', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $player = entrant($tournament);
    entrant($tournament);

    // Full but not started: leaving is still on offer.
    Livewire::actingAs($player)->test(TournamentPage::class, ['tournament' => $tournament->fresh()])
        ->assertSee('Leave');

    $tournament->update(['status' => TournamentEnum::GAMING]);

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

it('holds the API to the same rule once play has begun', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $player = entrant($tournament);
    entrant($tournament);
    app(CreateMatches::class)->execute($tournament->fresh());
    $tournament->update(['status' => TournamentEnum::GAMING]);

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
