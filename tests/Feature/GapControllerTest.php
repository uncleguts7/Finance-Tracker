<?php

use App\Models\Month;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

// ─── GET /api/gaps ───

it('returns empty gaps for a brand new user', function () {
    $this->actingAs($this->user)
        ->getJson('/api/gaps')
        ->assertOk()
        ->assertExactJson(['gaps' => []]);
});

it('returns empty gaps when every month has a row', function () {
    for ($m = 1; $m <= 8; $m++) {
        Month::forceCreate([
            'user_id' => $this->user->id,
            'year' => 2026,
            'month_number' => $m,
            'income' => 5000,
            'savings_goal' => 1000,
            'is_active' => false,
        ]);
    }

    $this->actingAs($this->user)
        ->getJson('/api/gaps')
        ->assertOk()
        ->assertExactJson(['gaps' => []]);
});

it('detects gaps between existing months', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 2, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $response = $this->actingAs($this->user)->getJson('/api/gaps');

    $response->assertOk();
    expect($response->json('gaps'))->toHaveCount(6)
        ->and($response->json('gaps.0'))->toEqual(['year' => 2026, 'month_number' => 3, 'label' => 'March 2026'])
        ->and($response->json('gaps.5'))->toEqual(['year' => 2026, 'month_number' => 8, 'label' => 'August 2026']);
});

it('excludes backfilled months from gaps', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 3, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $response = $this->actingAs($this->user)->getJson('/api/gaps');

    $response->assertOk();
    expect(collect($response->json('gaps'))->pluck('month_number')->toArray())->not->toContain(3);
});

it('excludes skipped months from gaps', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 3, 'income' => 0, 'savings_goal' => 0, 'is_skipped' => true, 'skipped_at' => now(), 'is_active' => false]);

    $response = $this->actingAs($this->user)->getJson('/api/gaps');

    $response->assertOk();
    expect(collect($response->json('gaps'))->pluck('month_number')->toArray())->not->toContain(3);
});

it('requires authentication for gaps', function () {
    $this->getJson('/api/gaps')->assertUnauthorized();
});

// ─── POST /api/gaps/backfill ───

it('backfills a gap month', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $response = $this->actingAs($this->user)->postJson('/api/gaps/backfill', [
        'year' => 2026,
        'month_number' => 3,
        'income' => 5000,
        'savings_goal' => 1000,
    ]);

    $response->assertCreated()
        ->assertJsonFragment(['year' => 2026, 'month_number' => 3, 'is_skipped' => false, 'income' => 5000, 'savings_goal' => 1000]);

    $this->assertDatabaseHas('months', [
        'user_id' => $this->user->id,
        'year' => 2026,
        'month_number' => 3,
        'is_skipped' => false,
        'income' => 5000,
        'savings_goal' => 1000,
    ]);
});

it('rejects backfill when month already exists', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 3, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $this->actingAs($this->user)
        ->postJson('/api/gaps/backfill', ['year' => 2026, 'month_number' => 3, 'income' => 4000, 'savings_goal' => 500])
        ->assertStatus(422)
        ->assertJson(['message' => 'This month already exists']);
});

it('validates backfill inputs', function () {
    $this->actingAs($this->user)
        ->postJson('/api/gaps/backfill', [])
        ->assertStatus(422);
});

// ─── POST /api/gaps/skip ───

it('skips a gap month', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $response = $this->actingAs($this->user)->postJson('/api/gaps/skip', [
        'year' => 2026,
        'month_number' => 3,
    ]);

    $response->assertCreated()
        ->assertJsonFragment(['year' => 2026, 'month_number' => 3, 'is_skipped' => true, 'income' => 0, 'savings_goal' => 0]);
    $this->assertDatabaseHas('months', [
        'user_id' => $this->user->id,
        'year' => 2026,
        'month_number' => 3,
        'is_skipped' => true,
    ]);
});

it('rejects skip when month already exists', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 3, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);

    $this->actingAs($this->user)
        ->postJson('/api/gaps/skip', ['year' => 2026, 'month_number' => 3])
        ->assertStatus(422)
        ->assertJson(['message' => 'This month already exists']);
});

it('validates skip inputs', function () {
    $this->actingAs($this->user)
        ->postJson('/api/gaps/skip', [])
        ->assertStatus(422);
});

// ─── Skipped month behaviour ───

it('returns null summary for a skipped month', function () {
    $month = Month::forceCreate([
        'user_id' => $this->user->id,
        'year' => 2026,
        'month_number' => 1,
        'income' => 0,
        'savings_goal' => 0,
        'is_active' => false,
        'is_skipped' => true,
        'skipped_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->getJson("/api/months/{$month->id}/summary")
        ->assertOk()
        ->assertJson([
            'is_skipped' => true,
            'income' => null,
            'savings_goal' => null,
            'total_spent' => null,
            'remaining' => null,
            'available_to_spend' => null,
            'is_overspent' => null,
            'spending_by_category' => [],
        ]);
});

it('includes skipped months in the month list', function () {
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 1, 'income' => 5000, 'savings_goal' => 1000, 'is_active' => false]);
    Month::forceCreate(['user_id' => $this->user->id, 'year' => 2026, 'month_number' => 2, 'income' => 0, 'savings_goal' => 0, 'is_skipped' => true, 'skipped_at' => now(), 'is_active' => false]);

    $response = $this->actingAs($this->user)->getJson('/api/months');

    $response->assertOk();
    expect(collect($response->json())->where('is_skipped', true))->toHaveCount(1);
});

it('requires authentication for backfill', function () {
    $this->postJson('/api/gaps/backfill', ['year' => 2026, 'month_number' => 3, 'income' => 5000, 'savings_goal' => 1000])
        ->assertUnauthorized();
});

it('requires authentication for skip', function () {
    $this->postJson('/api/gaps/skip', ['year' => 2026, 'month_number' => 3])
        ->assertUnauthorized();
});
