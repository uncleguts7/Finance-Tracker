<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: refuse to proceed if any NULL rows exist (would break FK semantics).
        $nullCount = DB::table('categories')->whereNull('user_id')->count();
        if ($nullCount > 0) {
            throw new RuntimeException(
                "Cannot tighten categories.user_id: {$nullCount} NULL row(s) exist. Backfill before running this migration."
            );
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
