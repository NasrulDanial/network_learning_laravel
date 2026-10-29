<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The link-health meter of the current try (0-100); refilled on every solve. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            if (! Schema::hasColumn('user_puzzle_progress', 'link_health')) {
                $table->unsignedTinyInteger('link_health')->default(100)->after('try_hint_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            $table->dropColumn('link_health');
        });
    }
};
