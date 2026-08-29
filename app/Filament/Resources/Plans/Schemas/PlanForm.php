<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->suffix('Toman'),
                TextInput::make('tournament_entries')
                    ->label('Tournament entries')
                    ->helperText('How many brackets this pass lets a player enter.')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(1),
                TextInput::make('vs_games')
                    ->label('VS games')
                    ->helperText('How many head-to-head matches this pass includes.')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}
