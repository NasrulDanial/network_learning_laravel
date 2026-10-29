<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stars are earned per try: these track the current try only and reset on every solve. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            if (! Schema::hasColumn('user_puzzle_progress', 'try_attempts')) {
                $table->unsignedTinyInteger('try_attempts')->default(0)->after('attempts_count');
            }
            if (! Schema::hasColumn('user_puzzle_progress', 'try_hint_used')) {
                $table->boolean('try_hint_used')->default(false)->after('hints_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_puzzle_progress', function (Blueprint $table) {
            $table->dropColumn(['try_attempts', 'try_hint_used']);
        });
    }
};
