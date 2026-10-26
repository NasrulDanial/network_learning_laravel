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
        Schema::create('puzzle_attempts', function (Blueprint $table) {
            $table->id('attempt_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('puzzle_id')->constrained('puzzles', 'puzzle_id')->onDelete('cascade');
            $table->integer('score');
            $table->string('status');
            $table->integer('attempt_no');
            $table->timestamp('date_taken')->useCurrent();
            $table->timestamps();
            // Compound index for fast per-user attempt lookups
            $table->index(['user_id', 'puzzle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_attempts');
    }
};
