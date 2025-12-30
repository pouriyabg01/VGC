<?php

namespace App\Enums\Tournaments;

use App\Traits\EnumValuesTrait;

enum TournamentMatchResultEnum:string
{
    use EnumValuesTrait;

    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CONFLICT = 'CONFLICT';

}
