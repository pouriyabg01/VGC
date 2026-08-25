<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('platform')
                    ->required()
                    ->options(PlatformEnum::options()),
                TextInput::make('game')
                    ->required(),
                TextInput::make('capacity')
                    ->numeric()
                    ->required()
                    ->live()
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            $n = (int) $value;
                            if ($n <= 0 || ($n & ($n - 1)) !== 0) {
                                $fail('The capacity must be a power of 2 (e.g., 2, 4, 8, 16).');
                            }
                        };
                    })
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, $record) {
                        if (! $record || ! $state) {
                            return;
                        }
                        $capacity = (int) $state;
                        $currentPlayers = (int) $record->current_player_count;

                        if ($currentPlayers >= $capacity) {
                            $set('status', TournamentEnum::READY->value);
                        } else {
                            $set('status', TournamentEnum::PENDING->value);
                        }
                    }),
                Select::make('status')
                    ->options(TournamentEnum::class)
                    ->hint('default value is pending')
                    ->default(TournamentEnum::PENDING->value)
                    ->required()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }
}
