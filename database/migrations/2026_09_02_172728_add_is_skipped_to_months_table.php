<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('months', function (Blueprint $table) {
            $table->boolean('is_skipped')->default(false)->after('is_active');
            $table->timestamp('skipped_at')->nullable()->after('is_skipped');
        });
    }

    public function down(): void
    {
        Schema::table('months', function (Blueprint $table) {
            $table->dropColumn(['is_skipped', 'skipped_at']);
        });
    }
};
