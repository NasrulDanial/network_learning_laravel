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
        // 1. Add academic and professional profile fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id', 50)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->string('staff_id', 50)->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('users', 'programme')) {
                $table->string('programme', 150)->nullable()->after('staff_id');
            }
            if (!Schema::hasColumn('users', 'semester')) {
                $table->unsignedTinyInteger('semester')->nullable()->after('programme');
            }
            if (!Schema::hasColumn('users', 'faculty')) {
                $table->string('faculty', 150)->nullable()->after('semester');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 150)->nullable()->after('faculty');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('department');
            }
        });

        // 2. Add rich card fields to topics table
        Schema::table('topics', function (Blueprint $table) {
            if (!Schema::hasColumn('topics', 'cover_image')) {
                $table->string('cover_image', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('topics', 'objectives')) {
                $table->text('objectives')->nullable()->after('cover_image');
            }
            if (!Schema::hasColumn('topics', 'difficulty')) {
                $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('objectives');
            }
            if (!Schema::hasColumn('topics', 'status')) {
                $table->enum('status', ['draft', 'published'])->default('published')->after('difficulty');
            }
            if (!Schema::hasColumn('topics', 'estimated_time_minutes')) {
                $table->unsignedSmallInteger('estimated_time_minutes')->default(30)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $columns = [];
            foreach (['cover_image', 'objectives', 'difficulty', 'status', 'estimated_time_minutes'] as $col) {
                if (Schema::hasColumn('topics', $col)) {
                    $columns[] = $col;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            foreach (['student_id', 'staff_id', 'programme', 'semester', 'faculty', 'department', 'phone'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $columns[] = $col;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
