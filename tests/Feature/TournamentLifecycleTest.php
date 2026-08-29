<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Plan;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\User;
use App\Services\CreateMatches;
use App\Services\TournamentService;
use Illuminate\Support\Facades\Auth;

/** A tournament filled to capacity by players who can legally sign up. */
function filledTournament(int $capacity = 4, array $overrides = []): Tournament
{
    $tournament = Tournament::factory()->create(array_merge([
        'capacity' => $capacity,
        'game' => 'fifa',
    ], $overrides));

    foreach (range(1, $capacity) as $i) {
        // A real pass, so it carries entries to spend. Attaching the pivot by
        // hand leaves the counters at zero and every sign-up is refused.
        $user = subscriber();
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
        ->and($tournament->matches()->count())->toBe(7);

    // The entry was spent at sign-up, not here. Finishing the tournament must
    // not charge the pass a second time, and must not take the VS games with
    // it — subscriber() sells 5 of each, so one tournament leaves 4 and 5.
    $pass = \DB::table('subscriptions')->first();

    expect($pass->tournament_entries_left)->toBe(4)
        ->and($pass->vs_games_left)->toBe(5)
        ->and((bool) $pass->status)->toBeTrue();
});
