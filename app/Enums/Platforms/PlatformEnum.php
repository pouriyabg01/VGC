<?php

namespace App\Enums\Platforms;

use App\Traits\EnumValuesTrait;

enum PlatformEnum: string
{
    use EnumValuesTrait;

    case PC = 'PC';
    case PLAYSTATION = 'PLAYSTATION';
    case XBOX = 'XBOX';
    case MOBILE = 'MOBILE';

    /**
     * Display name. The stored value is uppercase for consistency with
     * TournamentEnum, which is not how these read to a player.
     */
    public function label(): string
    {
        return match ($this) {
            self::PC => 'PC',
            self::PLAYSTATION => 'PlayStation',
            self::XBOX => 'Xbox',
            self::MOBILE => 'Mobile',
        };
    }

    /**
     * Values keyed by value, labels as the display side — for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
