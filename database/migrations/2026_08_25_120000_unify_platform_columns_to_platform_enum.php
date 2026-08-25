<?php

use App\Enums\Platforms\PlatformEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * platforms.platform stored ['pc','ps','xbox','mobile'] while
 * tournaments.platform stored ['PC','Playstation','Xbox','Mobile'], so a
 * player's platform could never be compared to a tournament's. Move both onto
 * PlatformEnum.
 *
 * Each column is widened to a string first: the existing enum constraint would
 * reject the new values, and the new one would reject the old rows, so the data
 * has to be rewritten in between.
 */
return new class extends Migration
{
    /** Old stored value => PlatformEnum value. */
    private const PLATFORMS_MAP = [
        'pc' => 'PC',
        'ps' => 'PLAYSTATION',
        'xbox' => 'XBOX',
        'mobile' => 'MOBILE',
    ];

    private const TOURNAMENTS_MAP = [
        'PC' => 'PC',
        'Playstation' => 'PLAYSTATION',
        'Xbox' => 'XBOX',
        'Mobile' => 'MOBILE',
    ];

    public function up(): void
    {
        $this->widen();

        foreach (self::PLATFORMS_MAP as $old => $new) {
            DB::table('platforms')->where('platform', $old)->update(['platform' => $new]);
        }

        foreach (self::TOURNAMENTS_MAP as $old => $new) {
            DB::table('tournaments')->where('platform', $old)->update(['platform' => $new]);
        }

        $this->narrow(PlatformEnum::values());
    }

    public function down(): void
    {
        $this->widen();

        foreach (array_flip(self::PLATFORMS_MAP) as $new => $old) {
            DB::table('platforms')->where('platform', $new)->update(['platform' => $old]);
        }

        foreach (array_flip(self::TOURNAMENTS_MAP) as $new => $old) {
            DB::table('tournaments')->where('platform', $new)->update(['platform' => $old]);
        }

        Schema::table('platforms', function (Blueprint $table) {
            $table->enum('platform', array_keys(self::PLATFORMS_MAP))->change();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->enum('platform', array_keys(self::TOURNAMENTS_MAP))->change();
        });
    }

    private function widen(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->string('platform', 20)->change();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('platform', 20)->change();
        });
    }

    private function narrow(array $allowed): void
    {
        Schema::table('platforms', function (Blueprint $table) use ($allowed) {
            $table->enum('platform', $allowed)->change();
        });

        Schema::table('tournaments', function (Blueprint $table) use ($allowed) {
            $table->enum('platform', $allowed)->change();
        });
    }
};
