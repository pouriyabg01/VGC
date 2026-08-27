<?php

namespace App\Filament\Resources\TournamentMatches;

use App\Filament\Resources\TournamentMatches\Pages\ListTournamentMatches;
use App\Filament\Resources\TournamentMatches\Pages\ViewTournamentMatch;
use App\Filament\Resources\TournamentMatches\Schemas\TournamentMatchInfolist;
use App\Filament\Resources\TournamentMatches\Tables\TournamentMatchesTable;
use App\Models\TournamentMatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Matches get their own resource so the tournament's Matches relation manager
 * has somewhere real to send a "view" click.
 *
 * Without it the relation manager fell back to the owner resource's schema and
 * rendered a match's values under a tournament's labels.
 */
class TournamentMatchResource extends Resource
{
    protected static ?string $model = TournamentMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $modelLabel = 'match';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * A match only means anything inside its tournament, so it is reached from
     * there rather than from its own sidebar entry. The pages stay routable.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TournamentMatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentMatchesTable::configure($table);
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        if (! $record instanceof TournamentMatch) {
            return null;
        }

        return 'Round '.$record->round.' — '
            .($record->player1?->name ?? 'TBD').' vs '.($record->player2?->name ?? 'TBD');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTournamentMatches::route('/'),
            'view' => ViewTournamentMatch::route('/{record}'),
        ];
    }
}
