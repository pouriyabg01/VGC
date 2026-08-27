<?php

namespace App\Filament\Resources\Tournaments\RelationManagers;

use App\Enums\Tournaments\TournamentEnum;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Models\TournamentMatch;
use App\Services\CreateMatches;
use App\Models\Tournament;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'matches';

    protected static ?string $relationshipTitle = 'Matches';

    /**
     * The rows here are matches, so this has to name the match resource. It
     * named TournamentResource, and since that resource's model is not the one
     * the rows carry, Filament could not link out to it — it fell back to a
     * modal built from the tournament's own infolist, which is why a match's
     * values appeared under "View tournament" beside tournament labels.
     *
     * Pointing it at a resource that owns this model also turns the view action
     * into a link to that resource's page instead of a modal.
     */
    protected static ?string $relatedResource = TournamentMatchResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('round')
                    ->sortable()
                    ->numeric(),
                TextColumn::make('player1.name')
                    ->label('player 1')
                    ->limit('10')
                    ->description(fn(TournamentMatch $record): string => 'goal: '.($record->winner_id !== null ? $record->player1_goal :'-')),
                TextColumn::make('player2.name')
                    ->label('player 2')
                    ->limit('10')
                    ->description(fn(TournamentMatch $record): string => 'goal: '.($record->winner_id !== null ? $record->player2_goal :'-')),
                TextColumn::make('winner.name')
                    ->limit('10'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('match_date')
                    ->label('end_at')
                    ->date()
            ])
            ->emptyStateHeading('No Matches')
            ->emptyStateDescription('Create a match to get started.')
            ->recordActions([
                ViewAction::make(),
            ])
            ->headerActions([
                Action::make('generateMatches')
                    ->label('Start Tournament Matches')
                    ->disabled(fn (): bool => $this->ownerRecord->status !== TournamentEnum::READY)
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (CreateMatches $service) {

                        $ownerRecord = $this->ownerRecord;

                        if (!($ownerRecord instanceof Tournament)) {
                            Notification::make()
                                ->title('Error')
                                ->body('Could not retrieve tournament details.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $result = $service->execute($ownerRecord);

                        if ($result['error'] !== null) {
                            Notification::make()
                                ->title('Error')
                                ->body($result['error']['message'])
                                ->danger()
                                ->send();
                            return;
                        }

                        $ownerRecord->update(['status' => TournamentEnum::GAMING]);
                        Notification::make()
                            ->title('Success')
                            ->body('Matches generated successfully!')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
