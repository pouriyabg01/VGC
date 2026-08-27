<?php

namespace App\Enums\Tournaments;

use App\Traits\EnumValuesTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;

enum TournamentMatchEnum: string implements HasColor
{
    use EnumValuesTrait;

    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case DISPUTED = 'DISPUTED';

    /** @return array<int|string, string> */
    public function getColor(): array
    {
        return match ($this) {
            self::PENDING => Color::Sky,        // waiting on the players
            self::DISPUTED => Color::Rose,      // waiting on an admin to judge
            self::COMPLETED => Color::Emerald,
        };
    }
}
