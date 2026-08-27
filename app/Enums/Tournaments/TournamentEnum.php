<?php

namespace App\Enums\Tournaments;

use App\Traits\EnumValuesTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;

/**
 * The colour lives on the enum rather than on each Filament column, so every
 * badge that renders a tournament status — the table, the infolist, anything
 * added later — reads the same one and they cannot drift apart.
 */
enum TournamentEnum: string implements HasColor
{
    use EnumValuesTrait;

    case PENDING = 'PENDING';
    case CANCELED = 'CANCELED';
    case READY = 'READY';
    case COMPLETED = 'COMPLETED';
    case GAMING = 'GAMING';

    /**
     * Palettes are returned straight from Color rather than as names like
     * 'info', so every status carries a colour of its own without any of them
     * having to be registered on the panel first.
     *
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::PENDING => Color::Sky,        // still filling up
            self::READY => Color::Violet,       // full, waiting for the draw
            self::GAMING => Color::Amber,       // being played right now
            self::COMPLETED => Color::Emerald,
            self::CANCELED => Color::Rose,
        };
    }
}
