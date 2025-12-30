<?php

namespace App\Enums\Tournaments;

use App\Traits\EnumValuesTrait;

enum TournamentEnum:string
{
    use EnumValuesTrait;
    case PENDING = 'PENDING';
    case CANCELED = 'CANCELED';
    case COMPLETED = 'COMPLETED';
    case GAMING = 'GAMING';
}
