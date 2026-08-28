<?php

use App\Enums\Tournaments\TournamentMatchEnum;
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
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round')->default('1');
            $table->index('round');
            $table->foreignId('player1_id')->constrained('users');
            $table->foreignId('player2_id')->constrained('users');
            $table->foreignId('winner_id')->nullable()->constrained('users');
            // "score", not "goal": the same column carries rounds won in
            // Tekken and races won in a racing game.
            $table->integer('player1_score')->default('0');
            $table->integer('player2_score')->default('0');
            $table->date('match_date')->nullable();
            // When the match stops waiting on the players. Null until the
            // match is drawn into a round.
            $table->dateTime('deadline_at')->nullable();
            $table->index('deadline_at');
            $table->enum('status' , TournamentMatchEnum::values())->default(TournamentMatchEnum::PENDING->value);
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
