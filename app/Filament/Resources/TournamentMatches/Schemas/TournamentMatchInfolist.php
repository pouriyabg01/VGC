<?php

namespace App\Filament\Resources\TournamentMatches\Schemas;

use App\Models\TournamentMatch;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TournamentMatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Match')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('tournament.game')
                            ->label('Tournament'),
                        TextEntry::make('round')
                            ->numeric(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('match_date')
                            ->label('Played at')
                            ->dateTime()
                            // Null until both reports agree, rather than missing.
                            ->placeholder('Not settled yet'),
                        TextEntry::make('created_at')
                            ->label('Drawn at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),

                Section::make('Score')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('player1.name')
                            ->label('Player 1')
                            ->placeholder('TBD'),
                        TextEntry::make('player2.name')
                            ->label('Player 2')
                            ->placeholder('TBD'),
                        TextEntry::make('winner.name')
                            ->label('Winner')
                            // A settled match always names a winner now that a
                            // level score is refused, so the only empty case
                            // left is a match that has not been settled.
                            ->placeholder('Undecided'),
                        TextEntry::make('deadline_at')
                            ->label('Report by')
                            ->dateTime()
                            ->placeholder('—')
                            ->color(fn (TournamentMatch $record) => $record->deadline_at?->isPast() ? 'danger' : null),
                        TextEntry::make('score')
                            ->label('Final score')
                            ->state(fn (TournamentMatch $record): string => $record->match_date === null
                                ? '—'
                                : $record->player1_score.' – '.$record->player2_score)
                            ->helperText('Player 1 – Player 2'),
                    ]),

                Section::make('Player reports')
                    ->description('What each player submitted, with the screenshot they attached as proof.')
                    ->schema([
                        RepeatableEntry::make('submissions')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Reported by'),
                                TextEntry::make('scored')
                                    ->label('Scored'),
                                TextEntry::make('conceded')
                                    ->label('Conceded'),
                                TextEntry::make('status')
                                    ->badge(),
                                ImageEntry::make('screenshot')
                                    ->label('Proof')
                                    ->disk('public')
                                    ->height(240)
                                    ->extraImgAttributes(['class' => 'rounded-lg object-contain'])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->hidden(fn (TournamentMatch $record): bool => $record->submissions->isEmpty()),

                Section::make('Player reports')
                    ->schema([
                        TextEntry::make('no_submissions')
                            ->hiddenLabel()
                            ->state('Neither player has reported a result yet.'),
                    ])
                    ->visible(fn (TournamentMatch $record): bool => $record->submissions->isEmpty()),
            ]);
    }
}
