<?php

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;

/** Queries run against the subscriptions pivot while $work runs. */
function subscriptionQueries(Closure $work): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $work();

    $count = collect(DB::getRawQueryLog())
        ->filter(fn ($q) => str_contains($q['raw_query'], 'subscriptions'))
        ->count();

    DB::disableQueryLog();

    return $count;
}

it('does not re-query for a user who has never subscribed', function () {
    $user = User::factory()->create();

    $subscriptions = app(SubscriptionService::class);

    $queries = subscriptionQueries(function () use ($subscriptions, $user) {
        $subscriptions->activeFor($user);
        $subscriptions->activeFor($user);
    });

    // A null result is still an answer; it must not fall through to the query.
    expect($queries)->toBe(1);
});

it('keeps users apart', function () {
    $holder = User::factory()->create();
    $holder->plan()->attach(Plan::factory()->create()->id, ['status' => true]);
    $stranger = User::factory()->create();

    $subscriptions = app(SubscriptionService::class);

    expect($subscriptions->activeFor($holder))->not->toBeNull()
        ->and($subscriptions->activeFor($stranger))->toBeNull();
});

it('sees a subscription taken out after an earlier miss', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['title' => 'Pro']);

    $subscriptions = app(SubscriptionService::class);

    expect($subscriptions->activeFor($user))->toBeNull();

    $subscriptions->subscribe($user, $plan);

    expect($subscriptions->activeFor($user)?->title)->toBe('Pro');
});

it('sees a subscription that was deactivated after being read', function () {
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);

    $subscriptions = app(SubscriptionService::class);

    expect($subscriptions->activeFor($user))->not->toBeNull();

    $subscriptions->deactivateFor([$user]);

    expect($subscriptions->activeFor($user))->toBeNull();
});

it('resolves the subscription once across the header and the page', function () {
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);

    // The header renders activeFor(), and Profile\Index asks again.
    $queries = subscriptionQueries(function () use ($user) {
        $this->actingAs($user)->get(route('profile'))->assertOk();
    });

    expect($queries)->toBe(1);
});
