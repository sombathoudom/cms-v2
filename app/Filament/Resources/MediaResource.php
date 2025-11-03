<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('File Details')
                ->schema([
                    TextInput::make('filename')->required(),
                    TextInput::make('uuid')->disabled(),
                    TextInput::make('disk')->required(),
                    TextInput::make('directory'),
                    TextInput::make('extension'),
                    TextInput::make('mime_type'),
                    TextInput::make('alt_text'),
                    TextInput::make('size')->numeric()->label('Size (bytes)'),
                    TextInput::make('width')->numeric()->nullable(),
                    TextInput::make('height')->numeric()->nullable(),
                ])->columns(2),
            KeyValue::make('meta')->keyLabel('Key')->valueLabel('Value')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')->searchable()->sortable(),
                TextColumn::make('mime_type')->sortable(),
                TextColumn::make('size')->formatStateUsing(fn (int $state): string => number_format($state / 1024, 2).' KB'),
                TextColumn::make('uploader.name')->label('Uploaded By'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('disk')->options(fn () => Media::query()->pluck('disk', 'disk')->unique()->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['filename', 'mime_type', 'alt_text'];
    }
}
