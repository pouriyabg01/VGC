<?php

use App\Enums\Platforms\PlatformEnum;
use App\Models\Plan;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

/**
 * A player who satisfies both sign-up preconditions: an active subscription,
 * and an account on the given platform.
 */
function playerOn(PlatformEnum $platform, string $nickname = 'tag'): User
{
    $user = subscriber();
    Platform::factory()->for($user)->on($platform)->create(['nickname' => $nickname]);

    return $user;
}

/**
 * A user with an active subscription and no platform accounts.
 *
 * Bought through the service rather than attached by hand, so the pass
 * carries the quota a real one would. Attaching the pivot directly leaves the
 * entry counters at zero, and every sign-up is then refused for having nothing
 * left on the pass.
 */
function subscriber(int $tournaments = 5, int $vsGames = 5): User
{
    $user = User::factory()->create();

    $plan = Plan::factory()->create([
        'tournament_entries' => $tournaments,
        'vs_games' => $vsGames,
    ]);

    app(\App\Services\SubscriptionService::class)->subscribe($user, $plan);

    return $user;
}
