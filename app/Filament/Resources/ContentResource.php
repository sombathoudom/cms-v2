<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentResource\Pages;
use App\Models\Category;
use App\Models\Content;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')
                        ->unique(table: Content::class, column: 'slug', ignoreRecord: true)
                        ->required(),
                    Select::make('type')
                        ->options([
                            'post' => 'Post',
                            'page' => 'Page',
                        ])->required(),
                    Textarea::make('excerpt')->rows(3),
                    RichEditor::make('body')->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Metadata')
                ->schema([
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->preload(),
                    Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload(),
                    Select::make('featured_media_id')
                        ->relationship('featuredMedia', 'filename')
                        ->label('Featured Media')
                        ->searchable(),
                    Toggle::make('is_sticky'),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'review' => 'Review',
                            'published' => 'Published',
                        ])->required(),
                    DateTimePicker::make('publish_at'),
                    DateTimePicker::make('scheduled_for'),
                    KeyValue::make('meta')->keyLabel('Key')->valueLabel('Value')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                BooleanColumn::make('is_sticky')->label('Pinned'),
                TextColumn::make('publish_at')->dateTime(),
                TextColumn::make('author.name')->label('Author'),
                TagsColumn::make('tags.name'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'review' => 'Review',
                    'published' => 'Published',
                ]),
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
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'excerpt'];
    }
}
