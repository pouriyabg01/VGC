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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            // Toman.
            $table->bigInteger('price');
            // What the pass actually buys. A plan is access to a number of
            // plays, not a ticket into one prize — which is also what keeps
            // the prize a fixed cost the site sets rather than a pool the
            // entrants fill.
            $table->unsignedInteger('tournament_entries')->default(1);
            $table->unsignedInteger('vs_games')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
