<?php

use App\Enums\Tournaments\TournamentMatchEnum;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Livewire\Livewire;

function matchBetween(User $a, User $b, array $overrides = []): TournamentMatch
{
    $tournament = Tournament::factory()->create(['game' => 'fifa', 'capacity' => 8]);

    return TournamentMatch::create(array_merge([
        'tournament_id' => $tournament->id,
        'player1_id' => $a->id,
        'player2_id' => $b->id,
        'round' => 1,
    ], $overrides));
}

it('says so when the player has no matches', function () {
    $user = User::factory()->create();

    expect($this->actingAs($user)->get(route('profile'))->assertOk()->getContent())
        ->toContain('My Matches')
        ->toContain('No matches yet');
});

it('lists a match with the opponent and offers a form while pending', function () {
    $user = User::factory()->create(['name' => 'Me']);
    $rival = User::factory()->create(['name' => 'Rival']);
    $match = matchBetween($user, $rival);

    $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toContain('Rival')
        ->and($html)->toContain('Round 1')
        ->and($html)->toContain('Submit result')
        ->and($html)->toContain('goals.'.$match->id.'.scored');
});

it('finds the match from either side of the draw', function () {
    $user = User::factory()->create(['name' => 'Me']);
    $rival = User::factory()->create(['name' => 'Rival']);
    matchBetween($rival, $user);   // user is player2 this time

    expect($this->actingAs($user)->get(route('profile'))->getContent())->toContain('Rival');
});

it('records a result and then shows it as submitted', function () {
    $user = User::factory()->create();
    $rival = User::factory()->create(['name' => 'Rival']);
    $match = matchBetween($user, $rival);

    Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 3)
        ->set("goals.{$match->id}.conceded", 1)
        ->call('submit', $match->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('match_results', [
        'tournament_match_id' => $match->id, 'user_id' => $user->id,
        'scored_goals' => 3, 'conceded_goals' => 1,
    ]);

    $html = $this->actingAs($user)->get(route('profile'))->getContent();
    expect($html)->toContain('Result submitted')
        ->and($html)->toContain('Waiting for Rival')
        ->and($html)->not->toContain('Submit result');
});

it('rejects missing or negative goals', function () {
    $user = User::factory()->create();
    $match = matchBetween($user, User::factory()->create());

    Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", -1)
        ->set("goals.{$match->id}.conceded", 0)
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect(\DB::table('match_results')->count())->toBe(0);
});

it('refuses a second submission from the same player', function () {
    $user = User::factory()->create();
    $match = matchBetween($user, User::factory()->create());

    $test = Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 2)
        ->set("goals.{$match->id}.conceded", 2)
        ->call('submit', $match->id)
        ->assertHasNoErrors();

    $test->set("goals.{$match->id}.scored", 5)
        ->set("goals.{$match->id}.conceded", 0)
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect(\DB::table('match_results')->where('user_id', $user->id)->count())->toBe(1);
});

it('refuses a player who is not in the match', function () {
    $outsider = User::factory()->create();
    $match = matchBetween(User::factory()->create(), User::factory()->create());

    Livewire::actingAs($outsider)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 1)
        ->set("goals.{$match->id}.conceded", 0)
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect(\DB::table('match_results')->count())->toBe(0);
});

it('settles the match and shows the score once both players agree', function () {
    $user = User::factory()->create();
    $rival = User::factory()->create();
    $match = matchBetween($user, $rival);

    Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 3)->set("goals.{$match->id}.conceded", 1)
        ->call('submit', $match->id);

    Livewire::actingAs($rival)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 1)->set("goals.{$match->id}.conceded", 3)
        ->call('submit', $match->id);

    $match->refresh();
    expect($match->status)->toBe(TournamentMatchEnum::COMPLETED)
        ->and($match->winner_id)->toBe($user->id);

    $html = $this->actingAs($user)->get(route('profile'))->getContent();
    expect($html)->toContain('You won')
        ->and($html)->not->toContain('Submit result');
});

it('marks the match disputed when the two reports disagree', function () {
    $user = User::factory()->create();
    $rival = User::factory()->create();
    $match = matchBetween($user, $rival);

    Livewire::actingAs($user)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 3)->set("goals.{$match->id}.conceded", 1)
        ->call('submit', $match->id);

    Livewire::actingAs($rival)->test(\App\Livewire\Profile\Matches::class)
        ->set("goals.{$match->id}.scored", 4)->set("goals.{$match->id}.conceded", 0)
        ->call('submit', $match->id);

    expect($match->refresh()->status)->toBe(TournamentMatchEnum::DISPUTED);

    // A disputed match no longer offers the form.
    expect($this->actingAs($user)->get(route('profile'))->getContent())
        ->not->toContain('Submit result');
});

it('offers no form once the match is settled', function () {
    $user = User::factory()->create();
    $match = matchBetween($user, User::factory()->create());

    // status is not in TournamentMatch::$fillable, so it cannot be mass
    // assigned; the app sets it by property, as the trait does.
    $match->status = TournamentMatchEnum::COMPLETED;
    $match->save();

    expect($match->fresh()->status)->toBe(TournamentMatchEnum::COMPLETED);
    expect($this->actingAs($user)->get(route('profile'))->getContent())
        ->not->toContain('goals.'.$match->id.'.scored');
});
