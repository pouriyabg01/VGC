<?php

use App\Models\Plan;
use App\Models\User;

it('lists every plan with a Get button pointing at its checkout', function () {
    $plans = Plan::factory()->count(3)->sequence(
        ['title' => 'Rookie'], ['title' => 'Pro'], ['title' => 'Elite'],
    )->create();

    $html = $this->get(route('plans'))->assertOk()->getContent();

    foreach ($plans as $plan) {
        expect($html)->toContain($plan->title)
            ->and($html)->toContain('Get '.$plan->title)
            ->and($html)->toContain(route('checkout', $plan));
    }
});

it('is public', function () {
    Plan::factory()->create(['title' => 'Rookie', 'price' => 2500]);

    $this->get(route('plans'))->assertOk()->assertSee('Rookie')->assertSee('2,500');
});

it('handles having no plans', function () {
    $this->get(route('plans'))->assertOk()->assertSee('No plans published yet.');
});

it('marks the plan the viewer already holds', function () {
    $held = Plan::factory()->create(['title' => 'Rookie']);
    $other = Plan::factory()->create(['title' => 'Pro']);
    $user = User::factory()->create();
    $user->plan()->attach($held->id, ['status' => true]);

    $html = $this->actingAs($user)->get(route('plans'))->assertOk()->getContent();

    expect($html)->toContain('Your current pass')
        // Neither card offers a purchase while a pass is running.
        ->and($html)->not->toContain('Get Rookie')
        ->and($html)->not->toContain('Get Pro');
});

it('offers every plan once the pass has lapsed', function () {
    $plan = Plan::factory()->create(['title' => 'Rookie']);
    $user = User::factory()->create();
    $user->plan()->attach($plan->id, ['status' => false]);

    $html = $this->actingAs($user)->get(route('plans'))->assertOk()->getContent();

    expect($html)->toContain('Get Rookie')
        ->and($html)->not->toContain('Your current pass');
});

it('offers plans to a guest', function () {
    Plan::factory()->create(['title' => 'Rookie']);

    expect($this->get(route('plans'))->getContent())->toContain('Get Rookie');
});

it('is where Browse plans on the profile leads', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toContain('Browse plans')
        ->and($html)->toContain(route('plans'))
        ->and($html)->not->toContain(route('home').'#plans');
});
