<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// ─── POST /api/register ───

it('registers a user and returns token with default categories', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    expect($response->json('user.email'))->toBe('jane@example.com');

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->categories()->count())->toBe(3)
        ->and($user->categories()->pluck('name')->toArray())->toEqualCanonicalizing(['Electricity', 'Gas', 'Water'])
        ->and($user->categories()->where('is_default', true)->count())->toBe(3);
});

it('fails registration with missing password confirmation', function () {
    $this->postJson('/api/register', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => 'password',
    ])->assertStatus(422);
});

it('fails registration with duplicate email', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422);
});

it('fails registration with short password', function () {
    $this->postJson('/api/register', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ])->assertStatus(422);
});

it('fails registration with missing name', function () {
    $this->postJson('/api/register', [
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422);
});

// ─── POST /api/login ───

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => Hash::make('correct'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong',
    ])->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials']);
});

it('rejects login with non-existent email', function () {
    $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ])->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials']);
});

it('fails login with missing email', function () {
    $this->postJson('/api/login', [
        'password' => 'password',
    ])->assertStatus(422);
});

// ─── POST /api/logout ───

it('logs out and invalidates the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJson(['message' => 'Logged out successfully']);

    // Token was deleted from DB
    expect($user->tokens()->count())->toBe(0);
});

it('requires authentication for logout', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});

// ─── GET /api/user ───

it('returns the authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/user');

    $response->assertOk()
        ->assertJson(['id' => $user->id, 'email' => $user->email]);
});

it('requires authentication for user endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
