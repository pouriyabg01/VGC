<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;

function matchIn(Tournament $t, int $round, ?TournamentMatchEnum $status = null): TournamentMatch
{
    $match = TournamentMatch::create([
        'tournament_id' => $t->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => $round,
    ]);

    if ($status) {
        // status is not fillable, so the app sets it by property.
        $match->status = $status;
        $match->save();
    }

    return $match;
}

it('counts the latest round per tournament, not across the table', function () {
    $a = Tournament::factory()->create(['capacity' => 8]);
    $b = Tournament::factory()->create(['capacity' => 8]);

    matchIn($a, 1); matchIn($a, 1);
    matchIn($a, 2);                  // A sits a round deeper than B
    matchIn($b, 1); matchIn($b, 1);

    // Reading the max round off a fresh query took it across every tournament,
    // so B looked for round 2 and counted zero.
    expect(count($a->matches()->latestRound()))->toBe(1)
        ->and(count($b->matches()->latestRound()))->toBe(2);
});

it('shows DONE! only once the tournament is completed', function () {
    $user = User::factory()->create();
    $t = Tournament::factory()->create(['capacity' => 8]);
    $t->players()->attach($user->id);
    matchIn($t, 1);

    $this->actingAs($user)->get(route('profile'))->assertDontSee('DONE!');

    $t->status = TournamentEnum::COMPLETED;
    $t->save();

    $this->actingAs($user)->get(route('profile'))->assertOk()->assertSee('DONE!');
});

it('leaves settled matches out of the count', function () {
    $t = Tournament::factory()->create(['capacity' => 16]);

    matchIn($t, 1, TournamentMatchEnum::COMPLETED);
    matchIn($t, 1, TournamentMatchEnum::DISPUTED);
    matchIn($t, 1);
    matchIn($t, 1);

    // Four matches drawn, one already played. A disputed one still has no
    // result, so it is not done with either.
    expect($t->matchesLeft())->toBe(3);
});

it('says how many are left on the tournament page', function () {
    $t = Tournament::factory()->create(['capacity' => 8]);

    matchIn($t, 1, TournamentMatchEnum::COMPLETED);
    matchIn($t, 1, TournamentMatchEnum::DISPUTED);
    matchIn($t, 1);

    $this->get(route('tournament', $t))->assertOk()->assertSee('2 left');
});

it('does not call a single drawn match a finished tournament', function () {
    $t = Tournament::factory()->create(['capacity' => 2]);
    matchIn($t, 1);

    // The page said DONE! whenever the tournament held exactly one match,
    // which is what a two-player final looks like the moment it is drawn.
    $this->get(route('tournament', $t))
        ->assertOk()
        ->assertDontSee('DONE!')
        ->assertSee('1 left');
});

it('does not count a tournament nobody has drawn yet', function () {
    $t = Tournament::factory()->create(['capacity' => 8]);

    // matchesLeft() answers 0 when no matches exist, and "0 left" read as a
    // tournament that had finished rather than one that had not begun.
    expect($t->matchesLabel())->toBe('Not started');
});

it('says the same thing on the landing, profile and tournament pages', function (string $expected, ?TournamentEnum $status) {
    $user = User::factory()->create();
    $t = Tournament::factory()->create(['capacity' => 8]);
    $t->players()->attach($user->id);

    if ($expected !== 'Not started') {
        matchIn($t, 1, TournamentMatchEnum::COMPLETED);
        matchIn($t, 1);
    }

    if ($status) {
        $t->status = $status;
        $t->save();
    }

    $this->get(route('tournament', $t))->assertOk()->assertSee($expected);
    $this->get(route('home'))->assertOk()->assertSee($expected);
    $this->actingAs($user)->get(route('profile'))->assertOk()->assertSee($expected);
})->with([
    ['Not started', null],
    ['1 left', null],
    ['DONE!', TournamentEnum::COMPLETED],
    ['Canceled', TournamentEnum::CANCELED],
]);
