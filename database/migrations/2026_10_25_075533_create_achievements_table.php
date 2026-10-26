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
        Schema::create('achievements', function (Blueprint $table) {
    $table->id('achievement_id'); 
    $table->string('badge_name'); 
    $table->text('description'); 
    $table->integer('xp_reward'); 
    $table->string('badge_icon')->nullable(); 
    $table->string('requirement_type'); 
    $table->integer('requirement_value'); 
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
