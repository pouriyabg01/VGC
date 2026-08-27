<?php

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Livewire\Livewire;

it('puts a Get button on every plan card, pointing at that plan checkout', function () {
    $plans = Plan::factory()->count(2)->sequence(
        ['title' => 'Rookie'],
        ['title' => 'Pro'],
    )->create();

    $html = $this->get('/')->assertOk()->getContent();

    foreach ($plans as $plan) {
        expect($html)->toContain('Get '.$plan->title)
            ->and($html)->toContain(route('checkout', $plan));
    }
});

it('shows the plan on the checkout page for a guest', function () {
    $plan = Plan::factory()->create(['title' => 'Rookie', 'price' => 2500]);

    $this->get(route('checkout', $plan))
        ->assertOk()
        ->assertSee('Rookie')
        ->assertSee('2,500')
        ->assertSee('Log in to confirm');
});

it('sends a guest to login instead of subscribing', function () {
    $plan = Plan::factory()->create();

    Livewire::test(\App\Livewire\Checkout::class, ['plan' => $plan])
        ->call('confirm')
        ->assertRedirect(route('login'));

    expect(\DB::table('subscriptions')->count())->toBe(0);
});

it('activates the subscription when a signed-in user confirms', function () {
    $plan = Plan::factory()->create(['title' => 'Pro']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Checkout::class, ['plan' => $plan])
        ->call('confirm')
        ->assertRedirect(route('profile'));

    $active = app(SubscriptionService::class)->activeFor($user->fresh());
    expect($active)->not->toBeNull()
        ->and($active->title)->toBe('Pro');

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 1,
    ]);
});

it('warns an already-subscribed user instead of offering the button', function () {
    $held = Plan::factory()->create(['title' => 'Rookie']);
    $other = Plan::factory()->create(['title' => 'Pro']);
    $user = User::factory()->create();
    $user->plan()->attach($held->id, ['status' => true]);

    $html = $this->actingAs($user)->get(route('checkout', $other))->assertOk()->getContent();

    expect($html)->toContain('Already subscribed')
        ->and($html)->toContain('Rookie')            // names the pass they hold
        ->and($html)->not->toContain('Confirm and activate');
});

it('refuses a second subscription even if confirm is called directly', function () {
    $held = Plan::factory()->create();
    $other = Plan::factory()->create();
    $user = User::factory()->create();
    $user->plan()->attach($held->id, ['status' => true]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Checkout::class, ['plan' => $other])
        ->call('confirm')
        ->assertHasErrors('confirm')
        ->assertNoRedirect();

    expect(\DB::table('subscriptions')->count())->toBe(1);
});

it('lets a user subscribe again once the previous pass is inactive', function () {
    $old = Plan::factory()->create();
    $new = Plan::factory()->create(['title' => 'Pro']);
    $user = User::factory()->create();
    $user->plan()->attach($old->id, ['status' => false]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Checkout::class, ['plan' => $new])
        ->call('confirm')
        ->assertRedirect(route('profile'));

    expect(app(SubscriptionService::class)->activeFor($user->fresh())->title)->toBe('Pro');
});
