<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Security';

    protected static ?string $navigationLabel = 'Audit Trail';

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user')->orderByDesc('created_at'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Occurred At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->searchable()
                    ->badge(),
                TextColumn::make('user.name')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Target Type')
                    ->toggleable(),
                TextColumn::make('auditable_id')
                    ->label('Target ID')
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(),
                TextColumn::make('properties')
                    ->label('Details')
                    ->formatStateUsing(static fn (?array $state): string => $state ? (json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '-') : '-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Actor')
                    ->relationship('user', 'email')
                    ->placeholder('All actors'),
                Filter::make('created_at_range')
                    ->label('Occurred Between')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $builder, string $from): Builder => $builder->whereDate('created_at', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $builder, string $until): Builder => $builder->whereDate('created_at', '<=', $until));
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }
}
