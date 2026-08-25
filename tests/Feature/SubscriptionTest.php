<?php

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('guest cannot subscribe to a plan but authenticated user with sanctum token can', function () {
    $user = User::factory()->create();

    $plan = Plan::create([
        'title' => 'adw',
        'description' => 'xbox',
        'price' => '200',
    ]);

    $response = $this->postJson("/api/subscription/plans/{$plan->id}");
    $response->assertStatus(401);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/subscription/plans/{$plan->id}");
    $response->assertStatus(200);

    $this->assertDatabaseHas('plan_user', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 1,
    ]);
});

test('authenticated user cannot subscribe again when already has an active subscription', function () {
    $user = User::factory()->create();

    $plan = new Plan();
    $plan->title = 'Basic';
    $plan->save();

    Sanctum::actingAs($user);

    $user->plan()->attach($plan->id, [
        'status' => true,
    ]);

    $response = $this->postJson("/api/subscriptions/{$plan->id}");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'you already have subscription!');
});

test('guest cannot view subscription but authenticated user with sanctum token can view active subscription', function () {
    $user = User::factory()->create();

    $plan = new Plan();
    $plan->title = 'Basic';
    $plan->save();

    $user->plan()->attach($plan->id, [
        'status' => true,
    ]);

    $response = $this->getJson('/api/subscriptions');
    $response->assertStatus(401);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/subscriptions');
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', "user's subscription")
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'sub_id',
                'user_id',
                'plan_id',
                'plan_title',
                'status',
                'started_at',
            ],
        ]);
});

test('authenticated user gets no subscription response when latest subscription is inactive', function () {
    $user = User::factory()->create();

    $plan = new Plan();
    $plan->title = 'Basic';
    $plan->save();

    $user->plan()->attach($plan->id, [
        'status' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/subscriptions');

    $response->assertStatus(401)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'have no subscription')
        ->assertJsonPath('data', []);
});
