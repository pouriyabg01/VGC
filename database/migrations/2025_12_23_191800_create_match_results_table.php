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
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_match_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->integer('scored_goals');
            $table->integer('conceded_goals');
            $table->enum('status',['pending' , 'confirmed' , 'conflict'])->default('pending');
            $table->unique(['tournament_match_id' , 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
