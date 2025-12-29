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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();
            $table->unsignedInteger('round')->default('1');
            $table->foreignId('player1')->constrained('users');
            $table->foreignId('player2')->constrained('users');
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->integer('player1_goal')->default('0');
            $table->integer('player2_goal')->default('0');
            $table->date('match_date')->nullable();
            $table->enum('status' ,['completed','pending','disputed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
