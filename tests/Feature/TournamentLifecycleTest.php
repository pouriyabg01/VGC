<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Plan;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\User;
use App\Services\CreateMatches;
use App\Services\TournamentService;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

/** A tournament filled to capacity by players who can legally sign up. */
function filledTournament(int $capacity = 4, array $overrides = []): Tournament
{
    $tournament = Tournament::factory()->create(array_merge([
        'capacity' => $capacity,
        'game' => 'fifa',
    ], $overrides));

    foreach (range(1, $capacity) as $i) {
        $user = User::factory()->create();
        $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);
        Platform::factory()->for($user)->create([
            'platform' => $tournament->platform,
            'nickname' => 'tag'.$i,
        ]);

        Auth::login($user);
        app(TournamentService::class)->signUp($user, $tournament);
    }

    Auth::logout();

    return $tournament->fresh();
}

it('refuses to let a player leave once the draw is made', function () {
    $tournament = filledTournament();
    app(CreateMatches::class)->execute($tournament);
    $tournament->update(['status' => TournamentEnum::GAMING]);

    $player = $tournament->players()->first();
    Auth::login($player);

    expect(fn () => app(TournamentService::class)->signOut($tournament->fresh()))
        ->toThrow(Exception::class);

    expect($tournament->fresh()->current_player_count)->toBe(4)
        ->and($tournament->players()->whereKey($player->id)->exists())->toBeTrue();
});

it('lets a player leave while sign-ups are still open', function () {
    $tournament = Tournament::factory()->create(['capacity' => 8]);
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);
    Platform::factory()->for($user)->create(['platform' => $tournament->platform, 'nickname' => 'x']);

    Auth::login($user);
    app(TournamentService::class)->signUp($user, $tournament);
    app(TournamentService::class)->signOut($tournament->fresh());

    expect($tournament->fresh()->current_player_count)->toBe(0)
        ->and($tournament->players()->count())->toBe(0);
});

it('refuses to draw a bracket for a canceled tournament', function () {
    $tournament = filledTournament(2);
    $tournament->update(['status' => TournamentEnum::CANCELED]);

    $result = app(CreateMatches::class)->execute($tournament->fresh());

    expect($result['error'])->not->toBeNull()
        ->and($tournament->matches()->count())->toBe(0);
});

it('plays a full eight-player bracket down to one champion', function () {
    $tournament = filledTournament(8);
    app(CreateMatches::class)->execute($tournament);
    $tournament->update(['status' => TournamentEnum::GAMING]);

    $bracket = new class {
        use App\Traits\TournamentMatchTrait;

        public function settle($m, $a, $b) { return $this->finalizeMatch($m, $a, $b); }
        public function next($t) { return $this->generateNextRound($t); }
    };

    for ($round = 1; $round <= 3; $round++) {
        $matches = $tournament->matches()->where('round', $round)->get();

        expect($matches)->toHaveCount(2 ** (3 - $round))
            ->and($matches->first()->deadline_at)->not->toBeNull();

        $matches->each(fn ($m) => $bracket->settle($m, 2, 1));
        $bracket->next($tournament->fresh());
    }

    $tournament->refresh();

    expect($tournament->status)->toBe(TournamentEnum::COMPLETED)
        ->and($tournament->winner_id)->not->toBeNull()
        ->and($tournament->matches()->count())->toBe(7)
        // The entry each player paid for is spent once the tournament ends.
        ->and(\DB::table('subscriptions')->where('status', true)->count())->toBe(0);
});
