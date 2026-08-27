<?php

namespace App\Enums\Tournaments;

use App\Traits\EnumValuesTrait;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;

enum TournamentMatchResultEnum: string implements HasColor
{
    use EnumValuesTrait;

    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CONFLICT = 'CONFLICT';

    /** @return array<int|string, string> */
    public function getColor(): array
    {
        return match ($this) {
            self::PENDING => Color::Sky,        // the other player has not reported yet
            self::CONFIRMED => Color::Emerald,
            self::CONFLICT => Color::Rose,
        };
    }
}
