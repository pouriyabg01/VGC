<?php

namespace App\Filament\Resources\Games\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Cover')
                    ->disk('public')
                    ->height(48)
                    ->defaultImageUrl(null)
                    ->placeholder('—'),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Running')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('voters_count')
                    ->label('Votes')
                    ->counts('voters')
                    ->badge()
                    ->color(fn ($state, $record) => $state >= $record->votes_target ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('votes_target')
                    ->label('Target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Running now')
                    ->placeholder('Everything')
                    ->trueLabel('Running')
                    ->falseLabel('Coming soon'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No games yet')
            ->emptyStateDescription('Add a game and it appears on the landing page.')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
