<?php

use App\Models\Plan;
use App\Models\User;

it('shows the icon while a subscription is active', function () {
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => true]);

    $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

    expect($html)->toContain('Active subscription')
        ->and($html)->toContain($user->name);
});

it('shows no icon when the user has no subscription at all', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('Active subscription')
        ->and($html)->toContain($user->name);   // the name still renders
});

it('shows no icon once the subscription has lapsed', function () {
    $user = User::factory()->create();
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => false]);

    $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('Active subscription');
});

it('drops the icon after the subscription is deactivated', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $user->plan()->attach($plan->id, ['status' => true]);

    expect($this->actingAs($user)->get('/')->getContent())->toContain('Active subscription');

    app(\App\Services\SubscriptionService::class)->deactivateFor([$user]);

    expect($this->actingAs($user->fresh())->get('/')->getContent())->not->toContain('Active subscription');
});

it('shows no icon to a guest', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('Active subscription');
});
