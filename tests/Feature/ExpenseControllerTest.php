<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Month;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── GET /api/months/{monthId}/expenses ───

it('lists expenses for a month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    Expense::forceCreate(['amount' => 100, 'date' => now()->toDateString(), 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $monthId]);
    Expense::forceCreate(['amount' => 50, 'date' => now()->toDateString(), 'type' => 'fixed', 'category_id' => $category->id, 'month_id' => $monthId]);

    $response = $this->getJson("/api/months/{$monthId}/expenses");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('returns empty list for month with no expenses', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');

    $this->getJson("/api/months/{$monthId}/expenses")
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('returns 404 when listing expenses for non-existent month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson('/api/months/999/expenses')->assertNotFound();
});

it('returns 404 when listing expenses for another users month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $otherMonth = Month::forceCreate(['user_id' => User::factory()->create()->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $this->getJson("/api/months/{$otherMonth->id}/expenses")->assertNotFound();
});

// ─── POST /api/months/{monthId}/expenses ───

it('creates an expense in the active month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);

    $response = $this->postJson("/api/months/{$monthId}/expenses", [
        'amount' => 250,
        'type' => 'variable',
        'category_id' => $category->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.amount', '250.00')
        ->assertJsonPath('data.type', 'variable')
        ->assertJsonPath('data.category_id', $category->id);
    $this->assertDatabaseHas('expenses', ['amount' => 250, 'type' => 'variable', 'month_id' => $monthId]);
});

it('creates an expense with a custom date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);

    $this->postJson("/api/months/{$monthId}/expenses", [
        'amount' => 100,
        'date' => '2026-01-15',
        'type' => 'fixed',
        'category_id' => $category->id,
    ])->assertCreated()
        ->assertJsonPath('data.date', '2026-01-15');
});

it('rejects creating expense in a past month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $pastMonth = Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $this->postJson("/api/months/{$pastMonth->id}/expenses", [
        'amount' => 100,
        'type' => 'variable',
        'category_id' => $category->id,
    ])->assertForbidden()
        ->assertJson(['message' => 'Cannot add expense to a past month']);
});

it('rejects creating expense with soft-deleted category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $category->delete();

    $this->postJson("/api/months/{$monthId}/expenses", [
        'amount' => 100,
        'type' => 'variable',
        'category_id' => $category->id,
    ])->assertStatus(422);
});

it('rejects creating expense with another users category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $otherCategory = Category::forceCreate(['name' => 'Theirs', 'default_type' => 'fixed', 'user_id' => User::factory()->create()->id]);

    $this->postJson("/api/months/{$monthId}/expenses", [
        'amount' => 100,
        'type' => 'variable',
        'category_id' => $otherCategory->id,
    ])->assertStatus(422);
});

it('validates expense creation inputs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');

    $this->postJson("/api/months/{$monthId}/expenses", [])
        ->assertStatus(422);
});

// ─── GET /api/expenses/{id} ───

it('shows an expense', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $expense = Expense::forceCreate(['amount' => 75, 'date' => now()->toDateString(), 'type' => 'fixed', 'category_id' => $category->id, 'month_id' => $monthId]);

    $this->getJson("/api/expenses/{$expense->id}")
        ->assertOk()
        ->assertJsonPath('data.amount', '75.00')
        ->assertJsonPath('data.type', 'fixed');
});

it('returns 404 for non-existent expense', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson('/api/expenses/999')->assertNotFound();
});

it('returns 404 for another users expense', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherUser = User::factory()->create();
    $otherMonth = Month::forceCreate(['user_id' => $otherUser->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    $otherCategory = Category::forceCreate(['name' => 'Theirs', 'default_type' => 'fixed', 'user_id' => $otherUser->id]);
    $otherExpense = Expense::forceCreate(['amount' => 50, 'date' => '2026-01-01', 'type' => 'fixed', 'category_id' => $otherCategory->id, 'month_id' => $otherMonth->id]);

    $this->getJson("/api/expenses/{$otherExpense->id}")->assertNotFound();
});

// ─── PUT /api/expenses/{id} ───

it('updates an expense in the active month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $expense = Expense::forceCreate(['amount' => 100, 'date' => now()->toDateString(), 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $monthId]);

    $response = $this->putJson("/api/expenses/{$expense->id}", [
        'amount' => 200,
        'type' => 'fixed',
        'category_id' => $category->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.amount', '200.00')
        ->assertJsonPath('data.type', 'fixed');
});

it('rejects updating an expense from a past month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $pastMonth = Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    $expense = Expense::forceCreate(['amount' => 100, 'date' => '2026-01-15', 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $pastMonth->id]);

    $this->putJson("/api/expenses/{$expense->id}", [
        'amount' => 999,
        'type' => 'fixed',
        'category_id' => $category->id,
    ])->assertForbidden()
        ->assertJson(['message' => 'Cannot update an expense from a past month']);
});

it('returns 404 when updating non-existent expense', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->putJson('/api/expenses/999', [
        'amount' => 100,
        'type' => 'fixed',
        'category_id' => 1,
    ])->assertNotFound();
});

it('validates expense update inputs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $expense = Expense::forceCreate(['amount' => 100, 'date' => now()->toDateString(), 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $monthId]);

    $this->putJson("/api/expenses/{$expense->id}", [])
        ->assertStatus(422);
});

// ─── DELETE /api/expenses/{id} ───

it('deletes an expense from the active month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $monthId = $this->getJson('/api/months')->json('data.0.id');
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $expense = Expense::forceCreate(['amount' => 100, 'date' => now()->toDateString(), 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $monthId]);

    $this->deleteJson("/api/expenses/{$expense->id}")
        ->assertOk()
        ->assertJson(['message' => 'Expense deleted successfully']);

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

it('rejects deleting an expense from a past month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::forceCreate(['name' => 'Food', 'default_type' => 'variable', 'user_id' => $user->id]);
    $pastMonth = Month::forceCreate(['user_id' => $user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    $expense = Expense::forceCreate(['amount' => 100, 'date' => '2026-01-15', 'type' => 'variable', 'category_id' => $category->id, 'month_id' => $pastMonth->id]);

    $this->deleteJson("/api/expenses/{$expense->id}")
        ->assertForbidden()
        ->assertJson(['message' => 'Cannot delete an expense from a past month']);
});

it('returns 404 when deleting non-existent expense', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->deleteJson('/api/expenses/999')->assertNotFound();
});

it('requires authentication', function () {
    $this->getJson('/api/months/1/expenses')->assertUnauthorized();
    $this->postJson('/api/months/1/expenses', ['amount' => 100, 'type' => 'variable', 'category_id' => 1])->assertUnauthorized();
    $this->getJson('/api/expenses/1')->assertUnauthorized();
    $this->putJson('/api/expenses/1', ['amount' => 100, 'type' => 'variable', 'category_id' => 1])->assertUnauthorized();
    $this->deleteJson('/api/expenses/1')->assertUnauthorized();
});
