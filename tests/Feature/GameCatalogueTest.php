<?php

use App\Filament\Resources\Games\Pages\CreateGame;
use App\Models\Admin;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(fn () => Storage::fake('public'));

/**
 * The Most wanted ranking alone.
 *
 * It sits above the shelf, and every title in it is also on a card below, so a
 * slice that runs to the end of the page proves nothing.
 */
function mostWantedBlock(string $html): string
{
    $start = strpos($html, 'Most wanted');
    $end = strpos($html, 'grid gap-4 grid-cols-2', $start);

    return substr($html, $start, $end - $start);
}

it('shows the catalogue on the landing page', function () {
    Game::create(['title' => 'Tekken 8']);
    Game::create(['title' => 'Gran Turismo 7']);

    $this->get(route('home'))->assertOk()
        ->assertSee('Tekken 8')
        ->assertSee('Gran Turismo 7');
});

it('leaves the section off the page when there are no games', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('What we run.');
});

it('renders an uploaded cover, and holds the shape without one', function () {
    $path = UploadedFile::fake()->image('tekken.jpg')->store('games', 'public');

    Game::create(['title' => 'Tekken 8', 'image' => $path]);
    Game::create(['title' => 'No Cover Game']);

    $html = $this->get(route('home'))->assertOk()->getContent();

    // A game with no cover must not render a broken <img>.
    expect($html)->toContain(Storage::disk('public')->url($path))
        ->and($html)->toContain('No cover');
});

it('lists the catalogue to anyone through the API', function () {
    Game::create(['title' => 'Tekken 8']);

    $this->getJson('/api/games')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Tekken 8')
        ->assertJsonPath('data.0.image_url', null);
});

it('lets an admin add a game with a cover', function () {
    Sanctum::actingAs(Admin::factory()->create());

    $response = $this->postJson('/api/games', [
        'title' => 'Street Fighter 6',
        'image' => UploadedFile::fake()->image('sf6.jpg'),
    ])->assertStatus(201);

    $game = Game::sole();

    Storage::disk('public')->assertExists($game->image);
    expect($response->json('data.image_url'))->toBe($game->imageUrl());
});

it('keeps the catalogue out of a plain player\'s hands', function () {
    $game = Game::create(['title' => 'Tekken 8']);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/games', ['title' => 'Anything'])->assertForbidden();
    $this->putJson("/api/games/{$game->id}", ['title' => 'Renamed'])->assertForbidden();
    $this->deleteJson("/api/games/{$game->id}")->assertForbidden();

    expect(Game::count())->toBe(1)
        ->and($game->fresh()->title)->toBe('Tekken 8');
});

it('refuses a guest outright', function () {
    $this->postJson('/api/games', ['title' => 'Anything'])->assertUnauthorized();
});

it('deletes the cover it replaces, and the one it removes', function () {
    Sanctum::actingAs(Admin::factory()->create());

    $this->postJson('/api/games', [
        'title' => 'Tekken 8',
        'image' => UploadedFile::fake()->image('old.jpg'),
    ]);

    $game = Game::sole();
    $old = $game->image;

    $this->putJson("/api/games/{$game->id}", [
        'image' => UploadedFile::fake()->image('new.jpg'),
    ])->assertOk();

    // Without this the disk fills with covers nothing points at.
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($game->fresh()->image);

    $this->deleteJson("/api/games/{$game->id}")->assertOk();

    Storage::disk('public')->assertMissing($game->fresh()?->image ?? 'gone');
    expect(Game::count())->toBe(0);
});

it('lets an admin add a game from the panel', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateGame::class)
        ->fillForm(['title' => 'Mortal Kombat 1'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Game::sole()->title)->toBe('Mortal Kombat 1');
});

it('counts a vote once however many times the button is pressed', function () {
    $game = Game::create(['title' => 'Tekken 8', 'votes_target' => 50, 'is_active' => true]);
    $player = User::factory()->create();

    $page = Livewire::actingAs($player)->test(\App\Livewire\Landing::class);

    $page->call('toggleVote', $game->id);
    expect($game->voters()->count())->toBe(1);

    // Pressing again takes the vote back rather than counting a second one.
    $page->call('toggleVote', $game->id);
    expect($game->voters()->count())->toBe(0);
});

it('sends a guest to log in instead of counting an anonymous vote', function () {
    $game = Game::create(['title' => 'Tekken 8']);

    Livewire::test(\App\Livewire\Landing::class)
        ->call('toggleVote', $game->id)
        ->assertRedirect(route('login'));

    expect($game->voters()->count())->toBe(0);
});

it('ranks the three most wanted, in order, above the shelf', function () {
    $quiet = Game::create(['title' => 'Quiet Game', 'is_active' => true]);
    $loud = Game::create(['title' => 'Loud Game', 'is_active' => true]);
    $middling = Game::create(['title' => 'Middling Game', 'is_active' => true]);
    Game::create(['title' => 'Fourth Game', 'is_active' => true]);

    $loud->voters()->attach(User::factory()->count(3)->create()->pluck('id'));
    $middling->voters()->attach(User::factory()->count(2)->create()->pluck('id'));
    $quiet->voters()->attach(User::factory()->create()->id);

    $html = $this->get(route('home'))->assertOk()->getContent();

    $ranking = mostWantedBlock($html);

    expect(strpos($ranking, 'Loud Game'))->toBeLessThan(strpos($ranking, 'Middling Game'))
        ->and(strpos($ranking, 'Middling Game'))->toBeLessThan(strpos($ranking, 'Quiet Game'))
        // Only three rows, so the fourth stays out of the ranking.
        ->and($ranking)->not->toContain('Fourth Game');
});

it('greys the vote out on a game that is not on yet', function () {
    $live = Game::create(['title' => 'FIFA 25', 'is_active' => true]);
    $soon = Game::create(['title' => 'Tekken 8']);

    $html = $this->get(route('home'))->assertOk()->getContent();

    // The coming-soon card keeps a button, disabled: a card with nothing where
    // its neighbours have one reads as broken.
    expect($html)->toContain('toggleVote('.$live->id.')')
        ->and($html)->not->toContain('toggleVote('.$soon->id.')')
        ->and($html)->toContain('Votes open at launch');
});

it('keeps a game nobody can vote for out of the ranking', function () {
    $soon = Game::create(['title' => 'Tekken 8']);
    $soon->voters()->attach(User::factory()->count(5)->create()->pluck('id'));
    Game::create(['title' => 'FIFA 25', 'is_active' => true]);

    $html = $this->get(route('home'))->assertOk()->getContent();
    $ranking = mostWantedBlock($html);

    // A row nobody can move is not a ranking, whatever votes it collected
    // before it was closed.
    expect($ranking)->toContain('FIFA 25')
        ->and($ranking)->not->toContain('Tekken 8');
});

it('refuses a vote for a game that is not on yet, on the page and through the API', function () {
    $game = Game::create(['title' => 'Tekken 8']);
    $player = User::factory()->create();

    // The button renders disabled, so this is the forged-click path.
    Livewire::actingAs($player)->test(\App\Livewire\Landing::class)
        ->call('toggleVote', $game->id);

    Sanctum::actingAs($player);
    $this->postJson("/api/games/{$game->id}/vote")->assertStatus(422);

    expect($game->voters()->count())->toBe(0);
});

it('lets an admin put a game on and take it off again through the API', function () {
    $game = Game::create(['title' => 'Tekken 8']);
    Sanctum::actingAs(Admin::factory()->create());

    $this->putJson("/api/games/{$game->id}", ['is_active' => true])
        ->assertOk()
        ->assertJsonPath('data.is_active', true);

    $this->putJson("/api/games/{$game->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

it('asks for the first vote rather than showing a row of zeros', function () {
    Game::create(['title' => 'Tekken 8', 'is_active' => true]);

    $this->get(route('home'))->assertOk()
        ->assertSee('Be first')
        ->assertSee('Nobody has asked yet');
});

it('fills the bar against the target, and never past it', function () {
    $game = Game::create(['title' => 'Tekken 8', 'votes_target' => 4]);
    $game->voters()->attach(User::factory()->count(2)->create()->pluck('id'));

    expect($game->fresh()->votePercent())->toBe(50);

    $game->voters()->attach(User::factory()->count(10)->create()->pluck('id'));

    // Past the target the bar is full; it does not overflow its track.
    expect($game->fresh()->votePercent())->toBe(100);
});

it('takes a vote through the API and gives it back', function () {
    $game = Game::create(['title' => 'Tekken 8', 'votes_target' => 50, 'is_active' => true]);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/games/{$game->id}/vote")
        ->assertOk()
        ->assertJsonPath('message', 'vote recorded')
        ->assertJsonPath('data.votes', 1);

    $this->postJson("/api/games/{$game->id}/vote")
        ->assertOk()
        ->assertJsonPath('message', 'vote withdrawn')
        ->assertJsonPath('data.votes', 0);
});

it('refuses a vote from an admin token', function () {
    $game = Game::create(['title' => 'Tekken 8', 'is_active' => true]);
    Sanctum::actingAs(Admin::factory()->create());

    // The pivot points at the users table, so an admin id would break the
    // foreign key — and a poll is players asking for a game.
    $this->postJson("/api/games/{$game->id}/vote")->assertForbidden();

    expect($game->voters()->count())->toBe(0);
});
