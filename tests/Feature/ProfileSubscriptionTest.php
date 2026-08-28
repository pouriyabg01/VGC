<?php

use App\Models\Plan;
use App\Models\User;

it('shows the pass details when one is active', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'title' => 'Pro Pass',
        'description' => 'Everything you need to compete.',
        'price' => 2500,
    ]);
    $user->plan()->attach($plan->id, ['status' => true]);

    $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toContain('My Subscription')
        ->and($html)->toContain('Pro Pass')
        ->and($html)->toContain('Everything you need to compete.')
        ->and($html)->toContain('2,500')
        ->and($html)->toContain('Active')
        ->and($html)->toContain(now()->format('M j, Y'))   // started date
        ->and($html)->not->toContain('No active pass');
});

it('offers plans when there is no subscription', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toContain('My Subscription')
        ->and($html)->toContain('No active pass')
        ->and($html)->toContain('Browse plans')
        ->and($html)->toContain(route('plans'));
});

it('treats a lapsed pass as none', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['title' => 'Old Pass']);
    $user->plan()->attach($plan->id, ['status' => false]);

    $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toContain('No active pass')
        ->and($html)->not->toContain('Old Pass');
});

it('shows the current pass, not a previous one', function () {
    $user = User::factory()->create();
    $old = Plan::factory()->create(['title' => 'Rookie Pass']);
    $new = Plan::factory()->create(['title' => 'Pro Pass']);

    $user->plan()->attach($old->id, ['status' => false]);
    $user->plan()->attach($new->id, ['status' => true]);

    $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toContain('Pro Pass')
        ->and($html)->not->toContain('Rookie Pass');
});
