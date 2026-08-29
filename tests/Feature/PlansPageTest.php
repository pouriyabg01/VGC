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

it('says what a pass buys, not just what it costs', function () {
    Plan::factory()->create([
        'title' => 'Gold',
        'price' => 250000,
        'tournament_entries' => 2,
        'vs_games' => 5,
    ]);

    $html = $this->get(route('plans'))->assertOk()->getContent();

    // The quota is the product; a price on its own does not say what is bought.
    expect($html)->toContain('2')
        ->and($html)->toContain('tournaments')
        ->and($html)->toContain('5')
        ->and($html)->toContain('VS games')
        ->and($html)->toContain('250,000')
        ->and($html)->toContain('Toman')
        // Prices are in Toman, and the pages used to print a dollar sign.
        ->and($html)->not->toContain('$250,000');
});

it('carries the quota through to checkout', function () {
    $plan = Plan::factory()->create([
        'title' => 'Silver', 'price' => 100000,
        'tournament_entries' => 1, 'vs_games' => 2,
    ]);

    $this->get(route('checkout', $plan))->assertOk()
        ->assertSee('1 tournament')
        ->assertSee('2 VS games')
        ->assertSee('100,000 Toman');
});

it('reads a single entry as singular', function () {
    Plan::factory()->create(['tournament_entries' => 1, 'vs_games' => 1]);

    $this->get(route('plans'))->assertOk()
        ->assertSee('tournament')
        ->assertDontSee('1 tournaments');
});

it('offers the landing plan card as a button, not a clickable card', function () {
    $plan = Plan::factory()->create(['title' => 'Gold', 'price' => 250000]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain(route('checkout', $plan))
        ->and($html)->toContain('Get Gold')
        // Prices are Toman here too; the landing card was still printing a
        // dollar sign after the other pages had been corrected.
        ->and($html)->toContain('250,000')
        ->and($html)->not->toContain('$250,000');
});

it('says which pass is held on the landing, the same as the plans page', function () {
    $held = Plan::factory()->create(['title' => 'Gold']);
    $other = Plan::factory()->create(['title' => 'Platinum']);

    $user = User::factory()->create();
    app(\App\Services\SubscriptionService::class)->subscribe($user, $held);

    $html = $this->actingAs($user)->get(route('home'))->assertOk()->getContent();

    // Offering a purchase the API would refuse is the thing this prevents.
    expect($html)->toContain('Your current pass')
        ->and($html)->toContain('Gold is active')
        ->and($html)->not->toContain('Get Gold')
        ->and($html)->not->toContain('Get Platinum');
});

it('still offers every plan on the landing to somebody with no pass', function () {
    Plan::factory()->create(['title' => 'Gold']);
    Plan::factory()->create(['title' => 'Platinum']);

    $html = $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('Get Gold')
        ->and($html)->toContain('Get Platinum')
        ->and($html)->not->toContain('Your current pass');
});
