<?php

use App\Enums\Platforms\PlatformEnum;
use App\Models\EntryUsage;
use App\Models\Plan;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TournamentService;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

/** A tournament on the platform the given player holds an account on. */
function openTournament(PlatformEnum $platform = PlatformEnum::XBOX): Tournament
{
    return Tournament::factory()->create(['platform' => $platform, 'capacity' => 8]);
}

/** A player with an account on the platform and a pass of the given size. */
function passHolder(int $tournaments, int $vsGames = 5): User
{
    $user = subscriber($tournaments, $vsGames);
    Platform::factory()->for($user)->create([
        'platform' => PlatformEnum::XBOX,
        'nickname' => 'tag'.$user->id,
    ]);

    return $user;
}

it('stocks a new pass from the plan it was bought from', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['tournament_entries' => 2, 'vs_games' => 5]);

    app(SubscriptionService::class)->subscribe($user, $plan);

    expect(app(SubscriptionService::class)->remainingFor($user))
        ->toBe(['tournaments' => 2, 'vs_games' => 5]);
});

it('keeps what was paid for when the plan is changed afterwards', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['tournament_entries' => 5, 'vs_games' => 10]);

    app(SubscriptionService::class)->subscribe($user, $plan);
    $plan->update(['tournament_entries' => 1, 'vs_games' => 1]);

    // The quota is copied onto the pass, not read through to the plan.
    expect(app(SubscriptionService::class)->remainingFor($user)['tournaments'])->toBe(5);
});

it('spends an entry when a player enters a tournament', function () {
    $user = passHolder(2);
    $tournament = openTournament();

    Auth::login($user);
    app(TournamentService::class)->signUp($user, $tournament);

    expect(app(SubscriptionService::class)->remainingFor($user)['tournaments'])->toBe(1);
});

it('records where each entry went', function () {
    $user = passHolder(2);
    $tournament = openTournament();

    Auth::login($user);
    app(TournamentService::class)->signUp($user, $tournament);

    $usage = EntryUsage::sole();

    // The counter says how many are left; this says where the rest went.
    expect($usage->type)->toBe(EntryUsage::TOURNAMENT)
        ->and($usage->user_id)->toBe($user->id)
        ->and($usage->tournament_id)->toBe($tournament->id);
});

it('refuses a sign-up once the entries are gone', function () {
    $user = passHolder(1);

    Auth::login($user);
    app(TournamentService::class)->signUp($user, openTournament());

    expect(fn () => app(TournamentService::class)->signUp($user, openTournament()))
        ->toThrow(Exception::class);

    expect(EntryUsage::count())->toBe(1);
});

it('leaves the seat alone when the entry could not be paid for', function () {
    $user = passHolder(1);
    Auth::login($user);
    app(TournamentService::class)->signUp($user, openTournament());

    $second = openTournament();

    try {
        app(TournamentService::class)->signUp($user, $second);
    } catch (Exception) {
        // expected
    }

    // A refused entry must not leave the player counted in the tournament.
    expect($second->fresh()->current_player_count)->toBe(0)
        ->and($second->players()->count())->toBe(0);
});

it('keeps the pass alive while VS games are left on it', function () {
    $user = passHolder(1, vsGames: 3);

    Auth::login($user);
    app(TournamentService::class)->signUp($user, openTournament());

    $remaining = app(SubscriptionService::class)->remainingFor($user);

    // Running out of tournaments must not throw away the head-to-heads.
    expect($remaining)->toBe(['tournaments' => 0, 'vs_games' => 3])
        ->and(app(SubscriptionService::class)->activeFor($user))->not->toBeNull();
});

it('closes the pass once nothing at all is left', function () {
    $user = passHolder(1, vsGames: 1);

    Auth::login($user);
    app(TournamentService::class)->signUp($user, openTournament());
    app(SubscriptionService::class)->spendVsGame($user);

    expect(app(SubscriptionService::class)->activeFor($user))->toBeNull()
        ->and(\DB::table('subscriptions')->value('status'))->toBe(0);
});

it('reports what is left through the API', function () {
    $user = passHolder(2, vsGames: 5);
    Sanctum::actingAs($user);

    $this->getJson('/api/subscription')
        ->assertOk()
        ->assertJsonPath('data.tournament_entries_left', 2)
        ->assertJsonPath('data.vs_games_left', 5);
});

it('shows what is left on the profile', function () {
    $user = passHolder(2, vsGames: 5);

    $this->actingAs($user)->get(route('profile'))->assertOk()
        ->assertSee('Tournaments left')
        ->assertSee('VS games left');
});
