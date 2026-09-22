<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Month;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── GET /api/months ───

it('lists the current month from middleware', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/months');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.year'))->toBe((int) now()->year)
        ->and($response->json('data.0.month_number'))->toBe((int) now()->month)
        ->and($response->json('data.0.is_active'))->toBeTrue();
});

it('lists all months for the user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $response = $this->getJson('/api/months');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('does not show other users months', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Month::forceCreate(['user_id' => User::factory()->create()->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $response = $this->getJson('/api/months');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

// ─── GET /api/months/{id} ───

it('shows a specific month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $month = Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 3000, 'savings_goal' => 500, 'is_active' => false]);

    $this->getJson("/api/months/{$month->id}")
        ->assertOk()
        ->assertJsonPath('data.year', 2026)
        ->assertJsonPath('data.month_number', 1)
        ->assertJsonPath('data.income', '3000.00')
        ->assertJsonPath('data.savings_goal', '500.00');
});

it('returns 404 for a non-existent month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson('/api/months/999')->assertNotFound();
});

it('returns 404 for another users month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $otherMonth = Month::forceCreate(['user_id' => User::factory()->create()->id, 'year' => 2026, 'month_number' => 1, 'income' => 3000, 'savings_goal' => 500, 'is_active' => false]);

    $this->getJson("/api/months/{$otherMonth->id}")->assertNotFound();
});

// ─── PUT /api/months/{id} ───

it('updates the active month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');

    $response = $this->putJson("/api/months/{$monthId}", [
        'income' => 6000,
        'savings_goal' => 1500,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.income', '6000.00')
        ->assertJsonPath('data.savings_goal', '1500.00');
});

it('rejects updating a past month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $pastMonth = Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 3000, 'savings_goal' => 500, 'is_active' => false]);

    $this->putJson("/api/months/{$pastMonth->id}", [
        'income' => 9999,
        'savings_goal' => 999,
    ])->assertForbidden()
        ->assertJson(['message' => 'Cannot update a past month']);
});

it('returns 404 updating non-existent month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->putJson('/api/months/999', [
        'income' => 5000,
        'savings_goal' => 1000,
    ])->assertNotFound();
});

it('validates month update inputs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');

    $this->putJson("/api/months/{$monthId}", [])
        ->assertStatus(422);
});

// ─── GET /api/months/{id}/summary ───

it('returns a summary for a month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $month = Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    Expense::forceCreate(['amount' => 200, 'date' => '2026-01-15', 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $month->id]);

    $response = $this->getJson("/api/months/{$month->id}/summary");

    $response->assertOk()
        ->assertJsonPath('data.income', '5000.00')
        ->assertJsonPath('data.savings_goal', '1000.00')
        ->assertJsonPath('data.total_spent', '200.00')
        ->assertJsonPath('data.remaining', '4800.00')
        ->assertJsonPath('data.available_to_spend', '4000.00')
        ->assertJsonPath('data.is_overspent', false);
    expect($response->json('data.spending_by_category'))->toHaveCount(1)
        ->and($response->json('data.spending_by_category.0.category_name'))->toBe('Food')
        ->and($response->json('data.spending_by_category.0.total'))->toBe('200.00');
});

it('returns 404 for summary of non-existent month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson('/api/months/999/summary')->assertNotFound();
});

it('requires authentication', function () {
    $this->getJson('/api/months')->assertUnauthorized();
    $this->getJson('/api/months/1')->assertUnauthorized();
    $this->putJson('/api/months/1', ['income' => 5000, 'savings_goal' => 1000])->assertUnauthorized();
    $this->getJson('/api/months/1/summary')->assertUnauthorized();
});
