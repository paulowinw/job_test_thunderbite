<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->unsignedInteger('daily_wins_limit')->nullable()->after('weight');
            $table->unsignedInteger('daily_wins_count')->nullable()->after('daily_wins_limit');
        });
    }

    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn(['daily_wins_limit', 'daily_wins_count']);
        });
    }
};
