<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress', function (Blueprint $table) {
            $table->id('progress_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('topic_id');
            $table->enum('learning_status', [
                'Not Started',
                'In Progress',
                'Completed',
            ])->default('Not Started');
            $table->enum('quiz_status', [
                'Not Attempted',
                'Passed',
                'Failed',
            ])->default('Not Attempted');
            $table->integer('progress_percentage')->default(0);
            $table->timestamps();

            $table->unique(
                ['user_id', 'topic_id'],
                'unique_student_topic'
            );

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress');
    }
};