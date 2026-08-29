<?php

namespace App\Filament\Resources\Games\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Game')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('image')
                    ->label('Cover')
                    ->helperText('JPG, PNG or WEBP. A full-size press cover is fine — it is shrunk to 900x1200 in the browser before it is sent.')
                    // Same disk and rules the API stores against, so a cover
                    // added here and one added there are the same thing.
                    ->disk('public')
                    ->directory('games')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    // Resized in the browser, before the upload leaves it.
                    // PHP here accepts 2 MB and drops anything larger before
                    // Laravel ever sees it, which is what produced the bare
                    // "failed to upload" on a press cover. Shrinking first
                    // means what reaches PHP is a couple of hundred KB.
                    // 3:4 is the aspect the landing card reserves.
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('900')
                    ->imageResizeTargetHeight('1200')
                    // The gate on the *original*: FilePond validates size when
                    // the file is picked and only transforms it on the way out,
                    // so a limit set to PHP's 2 MB would reject the cover
                    // before the resize could rescue it. This is deliberately
                    // Livewire's own temporary-upload ceiling.
                    ->maxSize(12288)
                    ->columnSpanFull(),
            ]);
    }
}
