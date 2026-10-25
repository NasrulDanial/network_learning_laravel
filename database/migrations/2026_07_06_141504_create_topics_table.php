<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id('topic_id');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->longText('content');
            $table->unsignedBigInteger('created_by');
            $table->integer('topic_order')->default(1);
            $table->timestamps();

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};