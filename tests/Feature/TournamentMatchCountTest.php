<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;

function matchIn(Tournament $t, int $round): TournamentMatch
{
    return TournamentMatch::create([
        'tournament_id' => $t->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => $round,
    ]);
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

it('counts nothing for a tournament with no matches', function () {
    expect(count(Tournament::factory()->create(['capacity' => 8])->matches()->latestRound()))->toBe(0);
});

it('shows the match count while the tournament is still running', function () {
    $user = User::factory()->create();
    $t = Tournament::factory()->create(['capacity' => 8]);
    $t->players()->attach($user->id);
    matchIn($t, 1);

    $this->actingAs($user)->get(route('profile'))
        ->assertOk()
        ->assertDontSee('DONE!');
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
