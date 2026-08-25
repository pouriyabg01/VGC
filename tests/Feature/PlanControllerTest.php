<?php

use App\Models\{Plan, User, Admin};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper to reduce repetition
 */
function adminToken() {
    $admin = Admin::factory()->create();
    return $admin->createToken('test-token')->plainTextToken;
}

/**
 * Scenario 1: Public Access (Guest/User)
 */
test('guests can list and view plans', function () {
    Plan::factory()->count(3)->create();
    $plan = Plan::factory()->create();

    // تست index
    $this->getJson('/api/plans')
        ->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'data']);

    $this->getJson("/api/plans/{$plan->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $plan->id);
});

/**
 * Scenario 2: Admin Access (Full CRUD)
 */
test('admin can perform full CRUD on plans', function () {
    $token = adminToken();
    $payload = ['title' => 'New Plan', 'description' => 'Desc', 'price' => 1000];

    $this->withToken($token)
        ->postJson('/api/plans', $payload)
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    $plan = Plan::where('title', 'New Plan')->first();
    $this->assertNotNull($plan);

    $updatePayload = ['title' => 'Updated Plan Name', 'description' => 'New Desc', 'price' => 2000];
    $this->withToken($token)
        ->putJson("/api/plans/{$plan->id}", $updatePayload)
        ->assertStatus(200);

    $this->assertDatabaseHas('plans', ['id' => $plan->id, 'title' => 'Updated Plan Name']);

    $this->withToken($token)
        ->deleteJson("/api/plans/{$plan->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

/**
 * Scenario 3: Unauthorized Access (Security Tests)
 */
test('unauthorized users cannot modify plans', function () {
    $user = User::factory()->create();
    $userToken = $user->createToken('user-token')->plainTextToken;
    $plan = Plan::factory()->create();

    //  store
    $response = $this->withToken($userToken)->postJson('/api/plans', ['title' => 'Hacker']);
    $this->assertTrue(in_array($response->getStatusCode(), [401, 403]));

    //  update
    $response = $this->withToken($userToken)->putJson("/api/plans/{$plan->id}", ['title' => 'Hacker Update']);
    $this->assertTrue(in_array($response->getStatusCode(), [401, 403]));

    //  destroy
    $response = $this->withToken($userToken)->deleteJson("/api/plans/{$plan->id}");
    $this->assertTrue(in_array($response->getStatusCode(), [401, 403]));

    $this->assertDatabaseHas('plans', ['id' => $plan->id]);
});

test('guests cannot modify plans', function () {
    $plan = Plan::factory()->create();

    $this->postJson('/api/plans', ['title' => 'Ghost'])->assertStatus(401);
    $this->putJson("/api/plans/{$plan->id}", ['title' => 'Ghost'])->assertStatus(401);
    $this->deleteJson("/api/plans/{$plan->id}")->assertStatus(401);
});
