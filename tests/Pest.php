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

/** A user with an active subscription and no platform accounts. */
function subscriber(): User
{
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);

    return $user;
}
