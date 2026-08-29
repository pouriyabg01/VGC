<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // How many players have to ask for a game before it is worth
            // running. Gives the bar a number that means something instead of
            // a share of an arbitrary total.
            $table->unsignedInteger('votes_target')->default(50)->after('image');
        });

        Schema::create('game_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // One player, one vote per game. Enforced here rather than only in
            // the app, so a double-clicked button cannot inflate a count.
            $table->unique(['game_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_user');

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('votes_target');
        });
    }
};
