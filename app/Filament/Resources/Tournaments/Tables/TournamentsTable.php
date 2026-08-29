<?php

namespace App\Filament\Resources\Tournaments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Resources\Users\UserResource;

class TournamentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('game')
                    ->searchable(),
                TextColumn::make('platform')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('capacity')
                    ->sortable(),
                TextColumn::make('current_player_count'),
                TextColumn::make('winner.name')
                    ->numeric()
                    ->label('winner')
                    ->url(fn ($record): string => $record->winner_id ? UserResource::getUrl('view', ['record' => $record->winner_id]) : '#')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // The same two the tournaments page and the API list by, so an
                // admin can reach a slice of the board the same way a player
                // describes it.
                SelectFilter::make('status')
                    ->options(TournamentEnum::values()),
                SelectFilter::make('platform')
                    ->options(PlatformEnum::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
