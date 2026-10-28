<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Optional lecturer-authored story framing for Mission Run (ticket, briefing, debrief, narrator). */
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('ticket_code', 20)->nullable()->after('title');
            $table->text('briefing_text')->nullable()->after('objective');
            $table->text('debrief_text')->nullable()->after('briefing_text');
            $table->string('narrator', 60)->nullable()->after('debrief_text');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn(['ticket_code', 'briefing_text', 'debrief_text', 'narrator']);
        });
    }
};
