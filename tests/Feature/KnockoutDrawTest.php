<?php

use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use App\Models\Admin;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Traits\TournamentMatchTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(fn () => Storage::fake('public'));

/** A tournament sitting on one open round-1 match. */
function drawFixture(array $overrides = []): TournamentMatch
{
    $tournament = Tournament::factory()->create(['game' => 'fifa', 'capacity' => 8]);

    return TournamentMatch::create(array_merge([
        'tournament_id' => $tournament->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => 1,
    ], $overrides));
}

/** Reaches the bracket logic the controllers and the panel share. */
function bracket(): object
{
    return new class {
        use TournamentMatchTrait;

        public function settle($match, $a, $b) { return $this->finalizeMatch($match, $a, $b); }
        public function draw($tournament) { return $this->generateNextRound($tournament); }
    };
}

it('refuses a level score from a player on the profile page', function () {
    $match = drawFixture();
    $user = User::find($match->player1_id);

    Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->set("scores.{$match->id}.scored", 2)
        ->set("scores.{$match->id}.conceded", 2)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('p.jpg'))
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    // Nothing is written, so the player can report again once they have played
    // it out rather than being locked out by their own refused attempt.
    expect(\DB::table('match_results')->count())->toBe(0)
        ->and($match->fresh()->status)->toBe(TournamentMatchEnum::PENDING);
});

it('refuses a level score from a player through the API', function () {
    $match = drawFixture();
    Sanctum::actingAs(User::find($match->player1_id));

    $this->postJson("/api/tournament-matches/{$match->id}/submit-result", [
        'scored' => 1,
        'conceded' => 1,
        'screenshot' => UploadedFile::fake()->image('p.jpg'),
    ])->assertStatus(422)->assertJsonValidationErrors('scored');

    expect(\DB::table('match_results')->count())->toBe(0);
});

it('refuses a level score from an admin through the API', function () {
    $match = drawFixture();
    Sanctum::actingAs(Admin::factory()->create());

    $this->putJson("/api/tournament-matches/{$match->id}/submit-by-admin", [
        'player1_score' => 3,
        'player2_score' => 3,
    ])->assertStatus(422)->assertJsonValidationErrors('player1_score');

    expect($match->fresh()->status)->toBe(TournamentMatchEnum::PENDING)
        ->and($match->fresh()->winner_id)->toBeNull();
});

it('refuses to judge a match the API has already settled', function () {
    $match = drawFixture();
    Sanctum::actingAs(Admin::factory()->create());

    $this->putJson("/api/tournament-matches/{$match->id}/submit-by-admin", [
        'player1_score' => 2, 'player2_score' => 0,
    ])->assertStatus(201);

    // The next round is drawn from the winner and an existing round is never
    // redrawn, so a second judgement would contradict the bracket.
    $this->putJson("/api/tournament-matches/{$match->id}/submit-by-admin", [
        'player1_score' => 0, 'player2_score' => 5,
    ])->assertStatus(422);

    expect($match->fresh()->winner_id)->toBe($match->player1_id);
});

it('pairs the next round in bracket order, not by player id', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $players = User::factory()->count(4)->create();
    $tournament->players()->attach($players->pluck('id'));

    // The winners are deliberately the two *highest* ids in one match and the
    // two lowest in the other, so ordering by user id would pair them
    // differently from the draw.
    $first = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[3]->id, 'player2_id' => $players[0]->id, 'round' => 1,
    ]);
    $second = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[2]->id, 'player2_id' => $players[1]->id, 'round' => 1,
    ]);

    $b = bracket();
    $b->settle($first, 3, 0);    // players[3] goes through
    $b->settle($second, 0, 2);   // players[1] goes through
    $b->draw($tournament->fresh());

    $final = $tournament->matches()->where('round', 2)->sole();

    expect([$final->player1_id, $final->player2_id])
        ->toBe([$players[3]->id, $players[1]->id]);
});

it('crowns the champion only after a real final', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);
    $players = User::factory()->count(4)->create();
    $tournament->players()->attach($players->pluck('id'));

    $one = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id, 'round' => 1,
    ]);
    $two = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $players[2]->id, 'player2_id' => $players[3]->id, 'round' => 1,
    ]);

    $b = bracket();
    $b->settle($one, 1, 0);
    $b->settle($two, 0, 1);
    $b->draw($tournament->fresh());

    // A drawn match used to erase both players, which could leave one winner
    // standing and end the tournament without a final ever being played.
    expect($tournament->fresh()->status)->not->toBe(TournamentEnum::COMPLETED)
        ->and($tournament->matches()->where('round', 2)->count())->toBe(1);

    $final = $tournament->matches()->where('round', 2)->sole();
    $b->settle($final, 2, 1);
    $b->draw($tournament->fresh());

    expect($tournament->fresh()->status)->toBe(TournamentEnum::COMPLETED)
        ->and($tournament->fresh()->winner_id)->toBe($players[0]->id);
});

it('refuses to draw a round from a winnerless completed match', function () {
    $tournament = Tournament::factory()->create(['capacity' => 4]);

    // The shape a pre-fix draw left behind: settled, but nobody went through.
    $stuck = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => 1,
    ]);
    $stuck->status = TournamentMatchEnum::COMPLETED;
    $stuck->save();

    expect(fn () => bracket()->draw($tournament->fresh()))
        ->toThrow(RuntimeException::class);
});
