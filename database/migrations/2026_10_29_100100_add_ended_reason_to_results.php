<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How a quiz attempt ended: 'finished', 'link_down' (out of lives) or 'abandoned'. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            if (! Schema::hasColumn('results', 'ended_reason')) {
                $table->string('ended_reason', 20)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn('ended_reason');
        });
    }
};
