<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\CreateMatches;

/** A round-1 match whose clock has already run out. */
function overdueMatch(array $overrides = []): TournamentMatch
{
    $tournament = Tournament::factory()->create(['capacity' => 8]);

    $match = TournamentMatch::create(array_merge([
        'tournament_id' => $tournament->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => 1,
        'deadline_at' => now()->subHour(),
    ], $overrides));

    return $match;
}

/** A report from one side of a match, bypassing the auth-bound creating() hook. */
function reportFor(TournamentMatch $match, int $userId, int $scored, int $conceded): void
{
    MatchResult::withoutEvents(fn () => MatchResult::create([
        'tournament_match_id' => $match->id,
        'user_id' => $userId,
        'screenshot' => 'conclusion-screenshot/x.jpg',
        'scored' => $scored,
        'conceded' => $conceded,
    ]));
}

it('starts the clock on the first round', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2, 'result_deadline_hours' => 24]);
    $tournament->players()->attach(User::factory()->count(2)->create()->pluck('id'));

    app(CreateMatches::class)->execute($tournament);

    $match = $tournament->matches()->sole();

    // Round one used to be the only round a no-show could stall forever.
    expect($match->deadline_at)->not->toBeNull()
        ->and($match->deadline_at->diffInHours(now()->addHours(24)))->toBeLessThan(1);
});

it('reads the window off the tournament rather than a fixed 24 hours', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2, 'result_deadline_hours' => 72]);
    $tournament->players()->attach(User::factory()->count(2)->create()->pluck('id'));

    app(CreateMatches::class)->execute($tournament);

    expect($tournament->matches()->sole()->deadline_at->diffInHours(now()->addHours(72)))
        ->toBeLessThan(1);
});

it('settles an overdue match on the one report that stands', function () {
    $match = overdueMatch();
    reportFor($match, $match->player1_id, 3, 1);

    $this->artisan('matches:forfeit-overdue')->assertSuccessful();

    $match->refresh();

    expect($match->status)->toBe(TournamentMatchEnum::COMPLETED)
        ->and($match->winner_id)->toBe($match->player1_id)
        ->and($match->player1_score)->toBe(3)
        ->and($match->player2_score)->toBe(1);
});

it('reads the standing report from its own side, not as an automatic win', function () {
    $match = overdueMatch();

    // player2 reports that they lost. Handing the win to whoever bothered to
    // report would crown the wrong player.
    reportFor($match, $match->player2_id, 0, 4);

    $this->artisan('matches:forfeit-overdue');

    $match->refresh();

    expect($match->winner_id)->toBe($match->player1_id)
        ->and($match->player1_score)->toBe(4)
        ->and($match->player2_score)->toBe(0);
});

it('sends an overdue match nobody reported to an admin', function () {
    $match = overdueMatch();

    $this->artisan('matches:forfeit-overdue');

    // There is no word to take, so a coin toss is not the answer.
    expect($match->fresh()->status)->toBe(TournamentMatchEnum::DISPUTED)
        ->and($match->fresh()->winner_id)->toBeNull();
});

it('leaves a match alone while it still has time', function () {
    $match = overdueMatch(['deadline_at' => now()->addHours(5)]);
    reportFor($match, $match->player1_id, 2, 0);

    $this->artisan('matches:forfeit-overdue');

    expect($match->fresh()->status)->toBe(TournamentMatchEnum::PENDING);
});

it('draws the next round once a forfeit finishes the round', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $players = User::factory()->count(4)->create();
    $tournament->players()->attach($players->pluck('id'));

    $settled = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id, 'round' => 1,
        'deadline_at' => now()->addDay(),
    ]);
    $settled->player1_score = 2;
    $settled->player2_score = 0;
    $settled->winner_id = $players[0]->id;
    $settled->status = TournamentMatchEnum::COMPLETED;
    $settled->save();

    $stalled = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[2]->id, 'player2_id' => $players[3]->id, 'round' => 1,
        'deadline_at' => now()->subMinute(),
    ]);
    reportFor($stalled, $players[2]->id, 1, 0);

    $this->artisan('matches:forfeit-overdue');

    $final = $tournament->matches()->where('round', 2)->sole();

    expect([$final->player1_id, $final->player2_id])
        ->toBe([$players[0]->id, $players[2]->id])
        ->and($final->deadline_at)->not->toBeNull();
});

it('finishes the tournament when a forfeit settles the final', function () {
    $tournament = Tournament::factory()->create(['capacity' => 2]);
    $players = User::factory()->count(2)->create();
    $tournament->players()->attach($players->pluck('id'));

    $final = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id, 'round' => 1,
        'deadline_at' => now()->subMinute(),
    ]);
    reportFor($final, $players[1]->id, 3, 2);

    $this->artisan('matches:forfeit-overdue');

    expect($tournament->fresh()->status)->toBe(TournamentEnum::COMPLETED)
        ->and($tournament->fresh()->winner_id)->toBe($players[1]->id);
});
