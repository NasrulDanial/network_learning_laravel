<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A badge may be earned once per student. Concurrent puzzle submissions could
     * otherwise both pass the application-level check and insert duplicates.
     */
    public function up(): void
    {
        $duplicates = DB::table('user_achievements')
            ->select('user_id', 'achievement_id')
            ->groupBy('user_id', 'achievement_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException(
                "user_achievements has {$duplicates} duplicated (user_id, achievement_id) pair(s). "
                . 'Remove the extra rows (keep the earliest date_earned) before running this migration.'
            );
        }

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->unique(['user_id', 'achievement_id'], 'user_achievements_user_achievement_unique');
        });
    }

    public function down(): void
    {
        // MySQL may be using the composite index for the user_id foreign key; give it a plain index first
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->index('user_id', 'user_achievements_user_id_index');
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropUnique('user_achievements_user_achievement_unique');
        });
    }
};
