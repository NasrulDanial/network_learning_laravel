<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One health pool per mission run: user_mission_progress.run_health (0-100, three segments = three
 * stars), and user_puzzle_progress.run_solved marks which activities the current run has solved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_mission_progress', function (Blueprint $table) {
            if (! Schema::hasColumn('user_mission_progress', 'run_health')) {
                $table->unsignedTinyInteger('run_health')->default(100);
            }
        });
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            if (! Schema::hasColumn('user_puzzle_progress', 'run_solved')) {
                $table->boolean('run_solved')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_mission_progress', fn (Blueprint $t) => $t->dropColumn('run_health'));
        Schema::table('user_puzzle_progress', fn (Blueprint $t) => $t->dropColumn('run_solved'));
    }
};
