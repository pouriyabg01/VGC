<?php

use App\Enums\Tournaments\TournamentMatchEnum;
use App\Enums\Tournaments\TournamentMatchResultEnum;
use App\Filament\Resources\TournamentMatches\Pages\ViewTournamentMatch;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
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
            'scored' => $scored,
            'conceded' => $conceded,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return $match->fresh();
}

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

it('offers a judge button on a disputed match', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->assertActionVisible('judge');
});

it('settles a disputed match on the score the admin enters', function () {
    $match = disputedMatch();
    [$one, $two] = [$match->player1, $match->player2];

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_score' => 2, 'player2_score' => 5]);

    $match->refresh();

    expect($match->status)->toBe(TournamentMatchEnum::COMPLETED)
        ->and($match->player1_score)->toBe(2)
        ->and($match->player2_score)->toBe(5)
        ->and($match->winner_id)->toBe($two->id)
        ->and($match->match_date)->not->toBeNull();

    // Both reports stop being open questions once the match is judged.
    expect($match->submissions->pluck('status')->unique()->all())
        ->toBe([TournamentMatchResultEnum::CONFIRMED]);
});

it('refuses to settle a knockout match level', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_score' => 1, 'player2_score' => 1])
        ->assertHasActionErrors(['player1_score']);

    // Left where it was: a level score used to complete the match with no
    // winner, and the next round then dropped both players from the draw.
    $match->refresh();

    expect($match->status)->toBe(TournamentMatchEnum::DISPUTED)
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
        ->callAction('judge', ['player1_score' => 3, 'player2_score' => 0]);

    expect($tournament->matches()->where('round', 2)->count())->toBe(0);

    Livewire::test(ViewTournamentMatch::class, ['record' => $other->getKey()])
        ->callAction('judge', ['player1_score' => 0, 'player2_score' => 2]);

    $next = $tournament->matches()->where('round', 2)->get();

    expect($next)->toHaveCount(1)
        ->and([$next->first()->player1_id, $next->first()->player2_id])
        ->toEqualCanonicalizing([$match->fresh()->winner_id, $other->fresh()->winner_id]);
});

it('hides the judge button once the match is settled', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_score' => 3, 'player2_score' => 0]);

    // Re-judging would leave the drawn bracket contradicting the new result,
    // because a round that already exists is never redrawn.
    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->assertActionHidden('judge');
});

it('refuses to judge without a score', function () {
    $match = disputedMatch();

    Livewire::test(ViewTournamentMatch::class, ['record' => $match->getKey()])
        ->callAction('judge', ['player1_score' => null, 'player2_score' => null])
        ->assertHasActionErrors(['player1_score' => 'required', 'player2_score' => 'required']);

    expect($match->fresh()->status)->toBe(TournamentMatchEnum::DISPUTED);
});
