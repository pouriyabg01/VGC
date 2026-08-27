<?php

use App\Livewire\Profile\Matches;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Support\MatchScreenshot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(fn () => Storage::fake('public'));

function screenshotMatch(User $a, User $b): TournamentMatch
{
    $tournament = Tournament::factory()->create(['game' => 'fifa', 'capacity' => 8]);

    return TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'player1_id' => $a->id,
        'player2_id' => $b->id,
        'round' => 1,
    ]);
}

it('files the screenshot under the match and player, and records that path', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 3)
        ->set("goals.{$match->id}.conceded", 1)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('score.jpg'))
        ->call('submit', $match->id)
        ->assertHasNoErrors();

    $stored = MatchResult::where('user_id', $user->id)->sole()->screenshot;

    expect($stored)->toStartWith("conclusion-screenshot/{$match->id}/{$user->id}/");

    // The path in the row must address the file as the disk sees it, or the
    // link on the page is dead and cleanup:screenshots deletes the file.
    Storage::disk('public')->assertExists($stored);
});

it('turns the recorded path into a working link on the profile page', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 2)
        ->set("goals.{$match->id}.conceded", 2)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('score.jpg'))
        ->call('submit', $match->id);

    $stored = MatchResult::where('user_id', $user->id)->sole()->screenshot;

    expect($this->actingAs($user)->get(route('profile'))->getContent())
        ->toContain('View your screenshot')
        ->toContain(Storage::disk('public')->url($stored));
});

it('refuses a submission with no screenshot', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 3)
        ->set("goals.{$match->id}.conceded", 1)
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect(MatchResult::count())->toBe(0);
});

it('refuses a file that is not an image', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 3)
        ->set("goals.{$match->id}.conceded", 1)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->create('cheat.pdf', 12, 'application/pdf'))
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect(MatchResult::count())->toBe(0);
});

it('accepts every format it advertises', function (string $format) {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 1)
        ->set("goals.{$match->id}.conceded", 0)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('score.'.$format))
        ->call('submit', $match->id)
        ->assertHasNoErrors();

    expect(MatchResult::count())->toBe(1);
})->with(MatchScreenshot::FORMATS);

it('refuses an image in a format it does not advertise', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    // A real image, just not one of the listed formats.
    expect(MatchScreenshot::FORMATS)->not->toContain('bmp');

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 1)
        ->set("goals.{$match->id}.conceded", 0)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('score.bmp'))
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect(MatchResult::count())->toBe(0);
});

it('refuses a file over the advertised size, and says the size in the error', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    $tooBig = UploadedFile::fake()->image('huge.jpg')->size(MatchScreenshot::MAX_KB + 1);

    $component = Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 1)
        ->set("goals.{$match->id}.conceded", 0)
        ->set("screenshots.{$match->id}", $tooBig)
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    expect($component->errors()->first('match.'.$match->id))
        ->toContain(MatchScreenshot::maxMb().' MB');

    expect(MatchResult::count())->toBe(0);
});

it('tells the player the formats and size before they pick a file', function () {
    $user = User::factory()->create();
    screenshotMatch($user, User::factory()->create());

    // The same constants that reject the file describe it on the form, so the
    // wording cannot drift away from the rule.
    expect($this->actingAs($user)->get(route('profile'))->getContent())
        ->toContain(MatchScreenshot::hint())
        ->toContain('accept="'.MatchScreenshot::accept().'"');
});

it('previews the picked file before it is submitted', function () {
    $user = User::factory()->create();
    screenshotMatch($user, User::factory()->create());

    expect($this->actingAs($user)->get(route('profile'))->getContent())
        ->toContain('createObjectURL')
        ->toContain('Screenshot preview');
});

it('writes nothing to disk when the submission is refused', function () {
    $outsider = User::factory()->create();
    $match = screenshotMatch(User::factory()->create(), User::factory()->create());

    Livewire::actingAs($outsider)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 1)
        ->set("goals.{$match->id}.conceded", 0)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('score.jpg'))
        ->call('submit', $match->id)
        ->assertHasErrors('match.'.$match->id);

    // The file is written only after the rules pass, so a refusal must not
    // leave an orphan behind.
    expect(Storage::disk('public')->allFiles('conclusion-screenshot'))->toBeEmpty();
});

it('takes the screenshot through the API too', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/tournament-matches/{$match->id}/submit-result", [
        'scored_goals' => 4,
        'conceded_goals' => 2,
        'screenshot' => UploadedFile::fake()->image('score.jpg'),
    ])->assertOk();

    $stored = MatchResult::where('user_id', $user->id)->sole()->screenshot;

    Storage::disk('public')->assertExists($stored);
    expect($response->json('data.screenshot'))->toBe($stored)
        ->and($response->json('data.screenshot_url'))->toBe(asset('storage/'.$stored));
});

it('holds the API to the same formats and size', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Sanctum::actingAs($user);

    $this->postJson("/api/tournament-matches/{$match->id}/submit-result", [
        'scored_goals' => 1, 'conceded_goals' => 0,
        'screenshot' => UploadedFile::fake()->create('cheat.pdf', 12, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('screenshot');

    $this->postJson("/api/tournament-matches/{$match->id}/submit-result", [
        'scored_goals' => 1, 'conceded_goals' => 0,
        'screenshot' => UploadedFile::fake()->image('huge.jpg')->size(MatchScreenshot::MAX_KB + 1),
    ])->assertStatus(422)->assertJsonValidationErrors('screenshot');

    expect(MatchResult::count())->toBe(0);
    expect(Storage::disk('public')->allFiles('conclusion-screenshot'))->toBeEmpty();
});

it('rejects an API submission with no screenshot', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Sanctum::actingAs($user);

    $this->postJson("/api/tournament-matches/{$match->id}/submit-result", [
        'scored_goals' => 4,
        'conceded_goals' => 2,
    ])->assertStatus(422)->assertJsonValidationErrors('screenshot');

    expect(MatchResult::count())->toBe(0);
});

it('leaves a referenced screenshot alone when cleanup runs', function () {
    $user = User::factory()->create();
    $match = screenshotMatch($user, User::factory()->create());

    Livewire::actingAs($user)->test(Matches::class)
        ->set("goals.{$match->id}.scored", 1)
        ->set("goals.{$match->id}.conceded", 0)
        ->set("screenshots.{$match->id}", UploadedFile::fake()->image('score.jpg'))
        ->call('submit', $match->id);

    $stored = MatchResult::where('user_id', $user->id)->sole()->screenshot;
    Storage::disk('public')->put('conclusion-screenshot/999/1/orphan.jpg', 'x');

    $this->artisan('cleanup:screenshots')->assertSuccessful();

    Storage::disk('public')->assertExists($stored);
    Storage::disk('public')->assertMissing('conclusion-screenshot/999/1/orphan.jpg');
});
