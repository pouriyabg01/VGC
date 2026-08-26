<?php

use App\Enums\Platforms\PlatformEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Carries a database created before the platform columns moved onto
 * PlatformEnum.
 *
 * create_platforms_table and create_tournaments_table now declare their
 * columns from the enum, which only helps a database built from scratch. A
 * database migrated earlier still holds the two original, incompatible sets:
 * platforms had ['pc','ps','xbox','mobile'] and tournaments had
 * ['PC','Playstation','Xbox','Mobile'].
 *
 * Safe to run on a database that is already converted: the maps below simply
 * match nothing, and re-declaring the column with the same enum is a no-op.
 */
return new class extends Migration
{
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
        // The old constraint rejects the new values and the new one rejects the
        // old rows, so the column has to pass through an unconstrained string
        // while the data is rewritten.
        $this->widen();

        foreach (self::PLATFORMS_MAP as $old => $new) {
            DB::table('platforms')->where('platform', $old)->update(['platform' => $new]);
        }

        foreach (self::TOURNAMENTS_MAP as $old => $new) {
            DB::table('tournaments')->where('platform', $old)->update(['platform' => $new]);
        }

        $this->setEnum(PlatformEnum::values(), PlatformEnum::values());
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

        $this->setEnum(array_keys(self::PLATFORMS_MAP), array_keys(self::TOURNAMENTS_MAP));
    }

    private function widen(): void
    {
        Schema::table('platforms', fn (Blueprint $t) => $t->string('platform', 20)->change());
        Schema::table('tournaments', fn (Blueprint $t) => $t->string('platform', 20)->change());
    }

    private function setEnum(array $platforms, array $tournaments): void
    {
        Schema::table('platforms', fn (Blueprint $t) => $t->enum('platform', $platforms)->change());
        Schema::table('tournaments', fn (Blueprint $t) => $t->enum('platform', $tournaments)->change());
    }
};
