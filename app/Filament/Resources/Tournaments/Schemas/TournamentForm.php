<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Enums\Tournaments\TournamentEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('platform')
                    ->required()
                    ->options([
                        'PC' => 'PC',
                        'Playstation' => 'Playstation',
                        'Xbox' => 'Xbox',
                        'Mobile' => 'Mobile',
                    ]),
                TextInput::make('game')
                    ->required(),
                TextInput::make('capacity')
                    ->numeric()
                    ->required(),
                Select::make('status')
                    ->options(TournamentEnum::class)
                    ->hint('default value is pending')
                    ->default('PENDING')
                    ->required()
                    ->disabled(),
            ]);
    }
}
