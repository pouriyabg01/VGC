<?php

namespace App\Enums\Tournaments;

use App\Traits\EnumValuesTrait;

enum TournamentMatchEnum:string
{
    use EnumValuesTrait;

    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case DISPUTED = 'DISPUTED';


}
