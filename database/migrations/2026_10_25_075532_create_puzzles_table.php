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
        Schema::create('puzzles', function (Blueprint $table) {
            $table->id('puzzle_id');
            // topic_id links directly to topics — no intermediate missions table needed
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->foreign('topic_id')->references('topic_id')->on('topics')->onDelete('set null');
            $table->enum('puzzle_type', ['matching', 'sequencing', 'classification', 'identification', 'drag_and_drop', 'scenario']);
            $table->text('question_text');
            $table->text('instruction');
            $table->json('correct_answer');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->integer('points');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzles');
    }
};
