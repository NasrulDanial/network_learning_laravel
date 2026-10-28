<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * hints_used: paid hint reveals for this activity (charged once, never reset).
     * best_stars: best 1–3 star rating earned (0 = not solved yet).
     */
    public function up(): void
    {
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            $table->unsignedTinyInteger('hints_used')->default(0)->after('xp_awarded');
            $table->unsignedTinyInteger('best_stars')->default(0)->after('hints_used');
        });

        Schema::table('user_mission_progress', function (Blueprint $table) {
            $table->unsignedTinyInteger('best_stars')->default(0)->after('best_score_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            $table->dropColumn(['hints_used', 'best_stars']);
        });

        Schema::table('user_mission_progress', function (Blueprint $table) {
            $table->dropColumn('best_stars');
        });
    }
};
