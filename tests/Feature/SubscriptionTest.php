<?php

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Laravel\Sanctum\Sanctum;

it('rejects guests', function () {
    $this->getJson('/api/subscription')->assertStatus(401);
});

it('subscribes a user to a plan', function () {
    Sanctum::actingAs($user = User::factory()->create());
    $plan = Plan::factory()->create(['title' => 'Basic']);

    $this->postJson("/api/subscription/plans/{$plan->id}")
        ->assertStatus(200)->assertJsonPath('data.plan_title', 'Basic');

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 1,
    ]);
});

it('refuses a second active subscription', function () {
    Sanctum::actingAs($user = User::factory()->create());
    $plan = Plan::factory()->create();
    $user->plan()->attach($plan->id, ['status' => true]);

    $this->postJson("/api/subscription/plans/{$plan->id}")
        ->assertStatus(404)->assertJsonPath('message', 'you already have subscription!');
});

it('shows the active subscription with a populated resource', function () {
    Sanctum::actingAs($user = User::factory()->create());
    $plan = Plan::factory()->create(['title' => 'Basic']);
    $user->plan()->attach($plan->id, ['status' => true]);

    $this->getJson('/api/subscription')
        ->assertStatus(200)
        ->assertJsonPath('message', "user's subscription")
        ->assertJsonPath('data.plan_title', 'Basic')
        ->assertJsonStructure(['data' => ['sub_id','user_id','plan_id','plan_title','status','started_at']]);
});

it('reports no subscription when the latest one is inactive', function () {
    Sanctum::actingAs($user = User::factory()->create());
    $user->plan()->attach(Plan::factory()->create()->id, ['status' => false]);

    $this->getJson('/api/subscription')
        ->assertStatus(401)->assertJsonPath('message', 'have no subscription');
});

it('deactivates subscriptions for a set of players', function () {
    $plan = Plan::factory()->create();
    $players = User::factory()->count(3)->create();
    foreach ($players as $p) {
        $p->plan()->attach($plan->id, ['status' => true]);
    }

    expect(app(SubscriptionService::class)->deactivateFor($players))->toBe(3);

    foreach ($players as $p) {
        expect($p->fresh()->plan()->wherePivot('status', true)->first())->toBeNull();
    }
});
