<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Whether the site actually puts this game on yet. Everything else
            // in the catalogue is on show as coming soon, and coming soon is
            // what a player votes for — there is nothing to ask for in a game
            // that is already running.
            $table->boolean('is_active')->default(false)->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
