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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('plan_id')->constrained();
            // True while the pass still has something left on it. Kept as a
            // flag rather than derived on read, because the header and the
            // sign-up policy both ask "is this usable" on every request.
            $table->boolean('status')->default(false);
            // Copied off the plan when the pass is bought, then counted down.
            // Held here rather than read off the plan so changing a plan's
            // quota later cannot retroactively change what someone already
            // paid for.
            $table->unsignedInteger('tournament_entries_left')->default(0);
            $table->unsignedInteger('vs_games_left')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription');
    }
};
