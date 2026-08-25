<?php

use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user with sanctum token can list their platforms', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    Platform::create([
        'user_id' => $user->id,
        'platform' => 'xbox',
        'nickname' => 'GamerX',
    ]);

    Platform::create([
        'user_id' => $user->id,
        'platform' => 'pc',
        'nickname' => 'GamerX',
    ]);

    $response = $this->getJson('/api/platform');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'all platforms')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user_id',
                'platforms' => [
                    '*' => ['id', 'nickname', 'platform']
                ]
            ]
        ]);
});

test('authenticated user with sanctum token can create a platform', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/platform', [
        'platform' => 'xbox',
        'nickname' => 'GamerX',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'platform created successfully!')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user_id',
                'platforms'
            ]
        ]);

    $this->assertDatabaseHas('platforms', [
        'user_id' => $user->id,
        'platform' => 'xbox',
        'nickname' => 'GamerX',
    ]);
});

test('authenticated user with sanctum token can update a platform', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $platform =   Platform::create([
        'user_id' => $user->id,
        'platform' => 'xbox',
        'nickname' => 'GamerX',
    ]);

    $response = $this->putJson("/api/platform/{$platform->id}", [
        'platform' => 'pc',
        'nickname' => 'NewName',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Platform updated successfully')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user_id',
                'platforms'
            ]
        ]);

    $this->assertDatabaseHas('platforms', [
        'id' => $platform->id,
        'user_id' => $user->id,
        'platform' => 'pc',
        'nickname' => 'NewName',
    ]);
});

test('authenticated user with sanctum token can delete a platform', function () {
    $user = User::factory()->create();


    $platform = Platform::create([
        'user_id' => $user->id,
        'platform' => 'xbox',
        'nickname' => 'GamerX',
    ]);
    $response = $this->deleteJson("/api/platform/{$platform->id}");
    $response->assertStatus(401);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/platform/{$platform->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('platforms', [
        'id' => $platform->id,
    ]);
});
