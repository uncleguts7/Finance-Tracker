<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serializes is_active and is_skipped as true/false across middleware and controller paths', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t')->plainTextToken;

    // Path A: middleware creates active month (first-ever /api/months request)
    $r1 = $this->withToken($token)->getJson('/api/months');
    $r1->assertOk();

    $isActiveMiddleware = $r1->json('data.0.is_active');
    $isSkippedMiddleware = $r1->json('data.0.is_skipped');

    // Must be real JSON booleans, not 1 / 0 ints / strings
    expect($isActiveMiddleware)->toBeTrue();
    expect(is_bool($isActiveMiddleware))->toBeTrue('is_active from middleware must be bool');
    expect($isSkippedMiddleware)->toBeFalse();
    expect(is_bool($isSkippedMiddleware))->toBeTrue('is_skipped from middleware must be bool');

    // Path B: controller creates backfilled past month (is_active false, is_skipped false)
    $r2 = $this->withToken($token)->postJson('/api/gaps/backfill', [
        'year' => 2026,
        'month_number' => 2,
        'income' => 3000,
        'savings_goal' => 500,
    ]);
    $r2->assertCreated();

    $isActiveBackfill = $r2->json('data.is_active');
    $isSkippedBackfill = $r2->json('data.is_skipped');

    expect($isActiveBackfill)->toBeFalse();
    expect(is_bool($isActiveBackfill))->toBeTrue('is_active from backfill must be bool');
    expect($isSkippedBackfill)->toBeFalse();
    expect(is_bool($isSkippedBackfill))->toBeTrue('is_skipped from backfill must be bool');

    // Path C: controller creates skipped month (is_active false, is_skipped true)
    $r3 = $this->withToken($token)->postJson('/api/gaps/skip', [
        'year' => 2026,
        'month_number' => 3,
    ]);
    $r3->assertCreated();

    $isActiveSkip = $r3->json('data.is_active');
    $isSkippedSkip = $r3->json('data.is_skipped');

    expect($isActiveSkip)->toBeFalse();
    expect(is_bool($isActiveSkip))->toBeTrue('is_active from skip must be bool');
    expect($isSkippedSkip)->toBeTrue();
    expect(is_bool($isSkippedSkip))->toBeTrue('is_skipped from skip must be bool');

    // Path D: re-fetch a middleware-created month via /months/{id} must stay bool
    $monthId = $r1->json('data.0.id');
    $r4 = $this->withToken($token)->getJson("/api/months/{$monthId}");
    $r4->assertOk();

    $isActiveRefetch = $r4->json('data.is_active');
    expect(is_bool($isActiveRefetch))->toBeTrue('re-fetched middleware month must stay bool');
});
