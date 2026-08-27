<?php

namespace App\Filament\Resources\TournamentMatches\Pages;

use App\Enums\Tournaments\TournamentMatchEnum;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\TournamentMatch;
use App\Traits\TournamentMatchTrait;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTournamentMatch extends ViewRecord
{
    // The panel settles a match through the same code the admin API uses, so a
    // result judged here behaves exactly like one submitted there.
    use TournamentMatchTrait;

    protected static string $resource = TournamentMatchResource::class;

    public function getTitle(): string
    {
        return 'View match';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->judgeAction(),

            // The way back to where the match was opened from.
            Action::make('tournament')
                ->label('Back to tournament')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->url(fn (TournamentMatch $record): string => TournamentResource::getUrl('view', [
                    'record' => $record->tournament_id,
                ])),
        ];
    }

    /**
     * Settles the match on the score the admin read off the screenshots.
     *
     * Hidden once the match is COMPLETED: the next round is drawn from the
     * winner, and generateNextRound() will not redraw a round that already
     * exists, so a second judgement would leave the bracket contradicting the
     * result it was built from.
     */
    private function judgeAction(): Action
    {
        return Action::make('judge')
            ->label('Judge match')
            ->icon('heroicon-o-scale')
            ->color('primary')
            ->authorize('submit')
            ->visible(fn (TournamentMatch $record): bool => $record->status !== TournamentMatchEnum::COMPLETED)
            ->modalHeading('Judge match')
            ->modalDescription('Enter the score you read off the screenshots. This settles the match, records the winner and draws the next round.')
            ->modalSubmitActionLabel('Confirm result')
            ->schema(fn (TournamentMatch $record): array => [
                TextInput::make('player1_goal')
                    ->label(($record->player1?->name ?? 'Player 1').' scored')
                    ->helperText($this->reportedBy($record, $record->player1_id))
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default($record->submissions->firstWhere('user_id', $record->player1_id)?->scored_goals),
                TextInput::make('player2_goal')
                    ->label(($record->player2?->name ?? 'Player 2').' scored')
                    ->helperText($this->reportedBy($record, $record->player2_id))
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default($record->submissions->firstWhere('user_id', $record->player2_id)?->scored_goals),
            ])
            ->action(function (array $data, TournamentMatch $record): void {
                $this->finalizeMatch($record, (int) $data['player1_goal'], (int) $data['player2_goal']);
                $this->generateNextRound($record->tournament);

                Notification::make()
                    ->title('Match settled')
                    ->body($record->winner?->name
                        ? $record->winner->name.' goes through.'
                        : 'The match is recorded as a draw, so nobody goes through.')
                    ->success()
                    ->send();
            });
    }

    /** What that player claimed, so the admin can see it beside the field. */
    private function reportedBy(TournamentMatch $record, ?int $userId): string
    {
        $submission = $record->submissions->firstWhere('user_id', $userId);

        if (! $submission) {
            return 'No report submitted.';
        }

        return 'Reported '.$submission->scored_goals.'–'.$submission->conceded_goals.'.';
    }
}
