<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionRelationManager extends RelationManager
{
    protected static string $relationship = 'plan';

    protected static ?string $label = 'Subscription';

    protected static ?string $relationshipTitle = 'Subscription';

    protected static ?string $relatedResource = UserResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state === 1 ? 'Active' : 'Disable')
                    ->color(fn ($state): string => $state === 1 ? 'success' : 'danger'),
                TextColumn::make('pivot.tournament_entries_left')
                    ->label('Tournaments left')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('pivot.vs_games_left')
                    ->label('VS games left')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Purchase Date')
                    ->date()
            ])
            ->emptyStateHeading('No Subscription')
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
