<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create missions table
        Schema::create('missions', function (Blueprint $table) {
            $table->id('mission_id');
            $table->unsignedBigInteger('topic_id');
            $table->foreign('topic_id')->references('topic_id')->on('topics')->onDelete('cascade');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('mission_order')->default(1);
            $table->integer('pass_mark')->default(70);
            $table->integer('xp_reward')->default(150);
            $table->enum('status', ['draft', 'published'])->default('published');
            $table->timestamps();
        });

        // 2. Enhance puzzles table to link to missions and separate public presentation from private answer key
        Schema::table('puzzles', function (Blueprint $table) {
            $table->unsignedBigInteger('mission_id')->nullable()->after('puzzle_id');
            $table->foreign('mission_id')->references('mission_id')->on('missions')->onDelete('cascade');
            $table->json('activity_data')->nullable()->after('instruction');
            $table->text('correct_feedback')->nullable()->after('hint');
            $table->text('incorrect_feedback')->nullable()->after('correct_feedback');
            $table->integer('activity_order')->default(1)->after('points');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('activity_order');
            $table->integer('max_attempts')->default(0)->after('difficulty');
            $table->boolean('is_required')->default(true)->after('max_attempts');
            $table->enum('status', ['draft', 'published'])->default('published')->after('is_required');
        });

        // 3. Create user_puzzle_progress table to track attempts and prevent XP farming
        Schema::create('user_puzzle_progress', function (Blueprint $table) {
            $table->id('user_puzzle_progress_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('puzzle_id');
            $table->integer('best_score')->default(0);
            $table->integer('attempts_count')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->integer('xp_awarded')->default(0);
            $table->timestamp('first_completed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('puzzle_id')->references('puzzle_id')->on('puzzles')->onDelete('cascade');
            $table->unique(['user_id', 'puzzle_id']);
        });

        // 4. Create user_mission_progress table for mission unlocking and pass mark evaluation
        Schema::create('user_mission_progress', function (Blueprint $table) {
            $table->id('user_mission_progress_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('mission_id');
            $table->boolean('is_unlocked')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->integer('best_score_percentage')->default(0);
            $table->boolean('xp_reward_granted')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('mission_id')->references('mission_id')->on('missions')->onDelete('cascade');
            $table->unique(['user_id', 'mission_id']);
        });

        // 5. Backfill existing puzzles into default missions per topic
        $topics = DB::table('topics')->orderBy('topic_order', 'asc')->get();
        foreach ($topics as $topic) {
            $existingPuzzles = DB::table('puzzles')->where('topic_id', $topic->topic_id)->get();
            
            // Create default mission for every existing topic
            $missionId = DB::table('missions')->insertGetId([
                'topic_id' => $topic->topic_id,
                'title' => 'Mission 1: ' . $topic->title . ' Core Lab',
                'description' => 'Hands-on interactive puzzle challenges for ' . $topic->title . '.',
                'objective' => 'Complete the interactive challenges to verify concept mastery and earn Network XP.',
                'difficulty' => 'beginner',
                'mission_order' => 1,
                'pass_mark' => 70,
                'xp_reward' => 150,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($existingPuzzles->count() > 0) {
                foreach ($existingPuzzles as $idx => $p) {
                    DB::table('puzzles')->where('puzzle_id', $p->puzzle_id)->update([
                        'mission_id' => $missionId,
                        'activity_order' => $idx + 1,
                        'correct_feedback' => 'Simulation verified across all nodes!',
                        'incorrect_feedback' => 'Configuration error detected. Review parameters and try again.',
                        'status' => 'published',
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mission_progress');
        Schema::dropIfExists('user_puzzle_progress');

        if (Schema::hasColumn('puzzles', 'mission_id')) {
            Schema::table('puzzles', function (Blueprint $table) {
                $table->dropForeign(['mission_id']);
                $table->dropColumn([
                    'mission_id',
                    'activity_data',
                    'correct_feedback',
                    'incorrect_feedback',
                    'activity_order',
                    'difficulty',
                    'max_attempts',
                    'is_required',
                    'status',
                ]);
            });
        }

        Schema::dropIfExists('missions');
    }
};
