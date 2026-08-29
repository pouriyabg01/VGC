<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every entry spent off a pass, and what it was spent on.
     *
     * The counters on the subscription answer "how many are left"; this
     * answers "where did they go". Without it the first player to say their
     * entries vanished cannot be answered, and a miscount cannot be traced.
     */
    public function up(): void
    {
        Schema::create('entry_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            // What it was spent on. Nullable because a VS game is not a
            // tournament, and because a tournament can be deleted without
            // rewriting history.
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_usages');
    }
};
