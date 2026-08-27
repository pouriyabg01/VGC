<?php

use App\Enums\Tournaments\TournamentMatchEnum;
use App\Enums\Tournaments\TournamentMatchResultEnum;
use App\Filament\Resources\TournamentMatches\Pages\ViewTournamentMatch;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Filament\Resources\Tournaments\Pages\ViewTournament;
use App\Filament\Resources\Tournaments\RelationManagers\MatchesRelationManager;
use App\Models\Admin;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = Admin::factory()->create();
    $this->actingAs($this->admin, 'admin');
});

function disputedMatch(): TournamentMatch
{
    $tournament = Tournament::factory()->create(['game' => 'fifa', 'capacity' => 8]);
    $one = User::factory()->create(['name' => 'Ali']);
    $two = User::factory()->create(['name' => 'Reza']);

    $match = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $one->id,
        'player2_id' => $two->id,
        'round' => 1,
    ]);

    // status is not fillable, so the app sets it by property.
    $match->status = TournamentMatchEnum::DISPUTED;
    $match->save();

    // MatchResult::creating() rewrites user_id to whoever is authenticated, and
    // here that is the admin — both rows would land on the same user and trip
    // the unique key. The fixture inserts them directly so each stays with the
    // player who reported it.
    foreach ([[$one, 3, 1], [$two, 4, 0]] as [$player, $scored, $conceded]) {
        $path = 'conclusion-screenshot/'.$match->id.'/'.$player->id.'/proof.jpg';

        // The entry only renders an image the disk actually holds, so the proof
        // has to be there and not just referenced.
        Storage::disk('public')->put($path, 'fake-jpeg-bytes');

        DB::table('match_results')->insert([
            'tournament_match_id' => $match->id,
            'user_id' => $player->id,
            'screenshot' => $path,
            'scored_goals' => $scored,
            'conceded_goals' => $conceded,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return $match->fresh();
}

it('opens a match on its own page, not in a tournament modal', function () {
    $match = disputedMatch();

    $this->get(TournamentMatchResource::getUrl('view', ['record' => $match]))
        ->assertOk()
        ->assertSee('View match')
        ->assertSee('Ali')
        ->assertSee('Reza');
});

it('does not label a match with the tournament\'s own fields', function () {
    $match = disputedMatch();

    // The old modal read TournamentResource's infolist, so a match was
    // described by tournament-only fields it does not even have.
    $this->get(TournamentMatchResource::getUrl('view', ['record' => $match]))
        ->assertOk()
        ->assertDontSee('Capacity')
        ->assertDontSee('Current player count')
        ->assertDontSee('View tournament');
});

it('shows what the players reported, with their screenshots', function () {
    $match = disputedMatch();

    $page = $this->get(TournamentMatchResource::getUrl('view', ['record' => $match]))->assertOk();

    $page->assertSee('Player reports')
        ->assertSee('Scored')
        ->assertSee('Conceded');

    // Both proofs are rendered from the paths recorded against the submissions.
    foreach ($match->submissions as $submission) {
        $page->assertSee(Storage::disk('public')->url($submission->screenshot), escape: false);
    }
});

it('shows the score and the dates of the match itself', function () {
    $match = disputedMatch();

    $this->get(TournamentMatchResource::getUrl('view', ['record' => $match]))
        ->assertOk()
        ->assertSee('Final score')
        ->assertSee('Played at')
        ->assertSee('Drawn at')
        ->assertSee(TournamentMatchEnum::DISPUTED->value);
});

it('says a match is not settled instead of leaving the fields blank', function () {
    $match = disputedMatch();

    expect($match->match_date)->toBeNull();

    $this->get(TournamentMatchResource::getUrl('view', ['record' => $match]))
        ->assertOk()
        ->assertSee('Not settled yet')
        ->assertSee('Undecided');
});

it('links the tournament relation manager straight at the match page', function () {
    $match = disputedMatch();

    Livewire::test(MatchesRelationManager::class, [
        'ownerRecord' => $match->tournament,
        'pageClass' => ViewTournament::class,
    ])
        ->assertTableActionHasUrl(
            'view',
            TournamentMatchResource::getUrl('view', ['record' => $match]),
            $match,
        );
});

it('offers a judge button on a disputed match', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->assertActionVisible('judge');
});

it('settles a disputed match on the score the admin enters', function () {
    $match = disputedMatch();
    [$one, $two] = [$match->player1, $match->player2];

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_goal' => 2, 'player2_goal' => 5]);

    $match->refresh();

    expect($match->status)->toBe(TournamentMatchEnum::COMPLETED)
        ->and($match->player1_goal)->toBe(2)
        ->and($match->player2_goal)->toBe(5)
        ->and($match->winner_id)->toBe($two->id)
        ->and($match->match_date)->not->toBeNull();

    // Both reports stop being open questions once the match is judged.
    expect($match->submissions->pluck('status')->unique()->all())
        ->toBe([TournamentMatchResultEnum::CONFIRMED]);
});

it('records a judged draw without inventing a winner', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_goal' => 1, 'player2_goal' => 1]);

    $match->refresh();

    expect($match->status)->toBe(TournamentMatchEnum::COMPLETED)
        ->and($match->winner_id)->toBeNull();
});

it('draws the next round once every match of the round is judged', function () {
    $match = disputedMatch();
    $tournament = $match->tournament;

    // A second match in the same round, so the round is only finished when
    // both are settled.
    $other = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => User::factory()->create()->id,
        'player2_id' => User::factory()->create()->id,
        'round' => 1,
    ]);

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_goal' => 3, 'player2_goal' => 0]);

    expect($tournament->matches()->where('round', 2)->count())->toBe(0);

    Livewire::test(ViewTournamentMatch::class, ['record' => $other->getKey()])
        ->callAction('judge', ['player1_goal' => 0, 'player2_goal' => 2]);

    $next = $tournament->matches()->where('round', 2)->get();

    expect($next)->toHaveCount(1)
        ->and([$next->first()->player1_id, $next->first()->player2_id])
        ->toEqualCanonicalizing([$match->fresh()->winner_id, $other->fresh()->winner_id]);
});

it('hides the judge button once the match is settled', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_goal' => 3, 'player2_goal' => 0]);

    // Re-judging would leave the drawn bracket contradicting the new result,
    // because a round that already exists is never redrawn.
    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->assertActionHidden('judge');
});

it('refuses to judge without a score', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_goal' => null, 'player2_goal' => null])
        ->assertHasActionErrors(['player1_goal' => 'required', 'player2_goal' => 'required']);

    expect($match->fresh()->status)->toBe(TournamentMatchEnum::DISPUTED);
});
