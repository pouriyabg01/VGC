<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register successfully', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Pouriya',
        'email' => 'pouriya@laravel.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'token', 'name']
        ]);

    $this->assertDatabaseHas('users', ['email' => 'pouriya@laravel.com']);
});

test('user cannot login with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'pouriya@laravel.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'pouriya@laravel.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user can login successfully', function () {
    $user = User::factory()->create([
        'email' => 'pouriya@laravel.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'pouriya@laravel.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});
