<?php

namespace App\Filament\Resources\TournamentMatches\Pages;

use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use Filament\Resources\Pages\ListRecords;

class ListTournamentMatches extends ListRecords
{
    protected static string $resource = TournamentMatchResource::class;
}
