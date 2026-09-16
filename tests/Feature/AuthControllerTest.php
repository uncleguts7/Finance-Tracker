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
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'token']]);

    expect($response->json('data.email'))->toBe('jane@example.com');

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
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'token']]);
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
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('requires authentication for user endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

// ─── PUT /api/user (Update Profile) ───

it('updates the authenticated user name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson('/api/user', [
            'name' => 'New Name',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.id', $user->id);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
});

it('returns created_at in user response after profile update', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson('/api/user', [
            'name' => 'Updated Name',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'created_at']]);
});

it('fails profile update with missing name', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user', [])
        ->assertStatus(422);
});

it('fails profile update with empty name', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user', ['name' => ''])
        ->assertStatus(422);
});

it('fails profile update with name too long', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user', ['name' => str_repeat('a', 256)])
        ->assertStatus(422);
});

it('requires authentication for profile update', function () {
    $this->putJson('/api/user', ['name' => 'New Name'])->assertUnauthorized();
});

// ─── PUT /api/user/password (Change Password) ───

it('changes password and returns new token', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $token = $user->createToken('auth-token')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    $response = $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'created_at', 'token']]);

    expect($response->json('data.token'))->not->toBeNull();

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(1); // Old token revoked, new one issued
});

it('fails password change with incorrect current password', function () {
    $user = User::factory()->create(['password' => Hash::make('correctpassword')]);
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.current_password.0', 'The current password is incorrect.');

    $user->refresh();
    expect(Hash::check('correctpassword', $user->password))->toBeTrue();
});

it('fails password change when new password matches current password', function () {
    $user = User::factory()->create(['password' => Hash::make('samepassword')]);
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'samepassword',
            'password' => 'samepassword',
            'password_confirmation' => 'samepassword',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.password.0', 'The new password must be different from the current password.');

    $user->refresh();
    expect(Hash::check('samepassword', $user->password))->toBeTrue();
});

it('fails password change with missing current password', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user/password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertStatus(422);
});

it('fails password change with short new password', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => '123',
            'password_confirmation' => '123',
        ])
        ->assertStatus(422);
});

it('fails password change with unconfirmed new password', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
        ])
        ->assertStatus(422);
});

it('revokes all existing tokens on password change', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;
    $token3 = $user->createToken('device-3')->plainTextToken;

    expect($user->tokens()->count())->toBe(3);

    $this->withToken($token1)
        ->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertOk();

    // All old tokens revoked, only new one exists
    expect($user->fresh()->tokens()->count())->toBe(1);
});

it('requires authentication for password change', function () {
    $this->putJson('/api/user/password', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertUnauthorized();
});
