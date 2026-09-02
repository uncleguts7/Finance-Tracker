<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── GET /api/categories ───

it('lists categories for the authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Category::forceCreate(['name' => 'Rent', 'default_type' => 'fixed', 'user_id' => $user->id]);

    $response = $this->getJson('/api/categories');

    $response->assertOk();
    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.name'))->toBe('Rent');
});

it('returns empty list when user has no categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJson([]);
});

it('does not show other users categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Category::forceCreate(['name' => 'Other Rent', 'default_type' => 'fixed', 'user_id' => User::factory()->create()->id]);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJson([]);
});

// ─── POST /api/categories ───

it('creates a category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/categories', [
        'name' => 'Groceries',
        'default_type' => 'variable',
    ]);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'Groceries', 'default_type' => 'variable']);

    $this->assertDatabaseHas('categories', [
        'name' => 'Groceries',
        'default_type' => 'variable',
        'user_id' => $user->id,
    ]);
});

it('fails creating a category with invalid type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson('/api/categories', [
        'name' => 'Bad',
        'default_type' => 'invalid',
    ])->assertStatus(422);
});

it('fails creating a category with missing name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson('/api/categories', [
        'default_type' => 'fixed',
    ])->assertStatus(422);
});

// ─── PUT /api/categories/{id} ───

it('updates a category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Rent', 'default_type' => 'fixed', 'user_id' => $user->id]);

    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => 'Mortgage',
        'default_type' => 'variable',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Mortgage', 'default_type' => 'variable']);
});

it('returns 404 when updating non-existent category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->putJson('/api/categories/999', [
        'name' => 'Ghost',
        'default_type' => 'fixed',
    ])->assertNotFound();
});

it('returns 404 when updating another users category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $otherCategory = Category::forceCreate(['name' => 'Theirs', 'default_type' => 'fixed', 'user_id' => User::factory()->create()->id]);

    $this->putJson("/api/categories/{$otherCategory->id}", [
        'name' => 'Mine now',
        'default_type' => 'variable',
    ])->assertNotFound();
});

it('fails updating with invalid type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Rent', 'default_type' => 'fixed', 'user_id' => $user->id]);

    $this->putJson("/api/categories/{$category->id}", [
        'name' => 'Rent',
        'default_type' => 'invalid',
    ])->assertStatus(422);
});

// ─── DELETE /api/categories/{id} ───

it('soft-deletes a category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Rent', 'default_type' => 'fixed', 'user_id' => $user->id]);

    $this->deleteJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJson(['message' => 'deleted successfully']);

    $this->assertSoftDeleted($category);
});

it('removes a soft-deleted category from the list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Rent', 'default_type' => 'fixed', 'user_id' => $user->id]);
    $category->delete();

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJson([]);
});

it('returns 404 when deleting non-existent category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->deleteJson('/api/categories/999')->assertNotFound();
});

it('returns 404 when deleting another users category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $otherCategory = Category::forceCreate(['name' => 'Theirs', 'default_type' => 'fixed', 'user_id' => User::factory()->create()->id]);

    $this->deleteJson("/api/categories/{$otherCategory->id}")->assertNotFound();
});

it('requires authentication', function () {
    $this->getJson('/api/categories')->assertUnauthorized();
    $this->postJson('/api/categories', ['name' => 'X', 'default_type' => 'fixed'])->assertUnauthorized();
    $this->putJson('/api/categories/1', ['name' => 'X', 'default_type' => 'fixed'])->assertUnauthorized();
    $this->deleteJson('/api/categories/1')->assertUnauthorized();
});
