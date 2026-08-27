<?php

namespace App\Filament\Resources\TournamentMatches\Tables;

use App\Models\TournamentMatch;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TournamentMatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('round')
                    ->sortable()
                    ->numeric(),
                TextColumn::make('player1.name')
                    ->label('player 1')
                    ->limit(10)
                    ->description(fn (TournamentMatch $record): string => 'goal: '.($record->winner_id !== null ? $record->player1_goal : '-')),
                TextColumn::make('player2.name')
                    ->label('player 2')
                    ->limit(10)
                    ->description(fn (TournamentMatch $record): string => 'goal: '.($record->winner_id !== null ? $record->player2_goal : '-')),
                TextColumn::make('winner.name')
                    ->limit(10),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('match_date')
                    ->label('end_at')
                    ->date(),
            ])
            ->emptyStateHeading('No Matches')
            ->emptyStateDescription('Create a match to get started.')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
