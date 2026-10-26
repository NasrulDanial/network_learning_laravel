<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            if (!Schema::hasColumn('progress', 'total_xp')) {
                $table->integer('total_xp')->default(0)->after('progress_percentage');
            }
            if (!Schema::hasColumn('progress', 'completed_missions')) {
                $table->integer('completed_missions')->default(0)->after('total_xp');
            }
            if (!Schema::hasColumn('progress', 'current_level')) {
                $table->integer('current_level')->default(1)->after('completed_missions');
            }
            if (!Schema::hasColumn('progress', 'current_rank')) {
                $table->string('current_rank')->default('Network Novice')->after('current_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            $table->dropColumn(['total_xp', 'completed_missions', 'current_level', 'current_rank']);
        });
    }
};
