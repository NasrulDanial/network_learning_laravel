<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id('quiz_id');
            $table->unsignedBigInteger('topic_id');
            $table->string('title', 150);
            $table->integer('passing_mark')->default(50);
            $table->timestamps();

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};