<?php

namespace Tests\Unit;

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament;
use App\Models\User;
use App\Services\CreateMatches;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMatchesTest extends TestCase
{
    use RefreshDatabase;

    private CreateMatches $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreateMatches();
    }

    /** @test */
    public function it_fails_if_player_count_is_not_power_of_2()
    {
        $tournament = Tournament::factory()->create();
        User::factory()->count(3)->create()->each(function ($user) use ($tournament) {
            $tournament->players()->attach($user);
        });

        $result = $this->service->execute($tournament);

        $this->assertNotNull($result['error']);
        $this->assertEquals('number of players should be power of 2', $result['error']['message']);
    }

    /** @test */
    public function it_fails_if_tournament_already_has_matches()
    {
        $tournament = Tournament::factory()->create();
        User::factory()->count(4)->create()->each(function ($user) use ($tournament) {
            $tournament->players()->attach($user);
        });

        $tournament->matches()->create([
            'player1_id' => User::factory()->create()->id,
            'player2_id' => User::factory()->create()->id,
        ]);

        $result = $this->service->execute($tournament);

        $this->assertNotNull($result['error']);
        $this->assertEquals('this tournament already has matches', $result['error']['message']);
    }

    /** @test */
    public function it_successfully_creates_matches_and_updates_status()
    {
        $tournament = Tournament::factory()->create(['status' => TournamentEnum::PENDING]);

        $players = User::factory()->count(4)->create();
        foreach ($players as $player) {
            $tournament->players()->attach($player);
        }

        $result = $this->service->execute($tournament);

        $this->assertNull($result['error']);
        $this->assertCount(2, $result['matches']);

        $this->assertEquals(TournamentEnum::GAMING, $tournament->fresh()->status);
    }
}
