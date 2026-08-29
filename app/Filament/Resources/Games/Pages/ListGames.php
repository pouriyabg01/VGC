<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\GameResource;
use App\Filament\Widgets\MostWantedGames;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add game'),
        ];
    }

    /**
     * The poll, under the catalogue it ranks.
     *
     * It used to sit on the dashboard, a page away from the games it is about.
     */
    protected function getFooterWidgets(): array
    {
        return [
            MostWantedGames::class,
        ];
    }
}
