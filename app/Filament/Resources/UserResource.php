<?php

namespace App\Filament\Resources;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\PasswordRules;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Profile')
                ->schema([
                    Grid::make()
                        ->schema([
                            TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(table: User::class, column: 'email', ignoreRecord: true),
                        ])->columns(2),
                    Grid::make()
                        ->schema([
                            TextInput::make('password')
                                ->password()
                                ->label('Password')
                                ->required(fn (?User $record) => $record === null)
                                ->rules(fn (?User $record) => array_merge(
                                    $record === null ? ['required', 'confirmed'] : ['nullable', 'confirmed'],
                                    PasswordRules::forUser($record)
                                ))
                                ->dehydrated(fn (?string $state) => filled($state))
                                ->maxLength(255),
                            TextInput::make('password_confirmation')
                                ->password()
                                ->label('Confirm Password')
                                ->same('password')
                                ->dehydrated(false)
                                ->required(fn (?User $record, string $context) => $context === 'create'),
                        ])->columns(2),
                ]),
            Section::make('Status & Roles')
                ->schema([
                    Select::make('status')
                        ->options(self::statusOptions())
                        ->required()
                        ->default(UserStatus::ACTIVE->value),
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => UserStatus::ACTIVE->value,
                        'warning' => UserStatus::INACTIVE->value,
                        'danger' => UserStatus::SUSPENDED->value,
                    ])
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(', ')
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name'),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => ! $record->is(auth()->user()))
                    ->authorize(fn (User $record) => auth()->user()?->can('delete', $record) ?? false)
                    ->after(fn (User $record) => self::logAction('user.deleted', $record)),
                Tables\Actions\RestoreAction::make()
                    ->authorize(fn (User $record) => auth()->user()?->can('restore', $record) ?? false)
                    ->after(fn (User $record) => self::logAction('user.restored', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->authorize(fn () => auth()->user()?->can('users.update') ?? false)
                        ->action(fn (Collection $records) => self::bulkUpdateStatus($records, UserStatus::ACTIVE)),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-m-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->authorize(fn () => auth()->user()?->can('users.update') ?? false)
                        ->action(fn (Collection $records) => self::bulkUpdateStatus($records, UserStatus::INACTIVE)),
                    Tables\Actions\DeleteBulkAction::make()
                        ->authorize(fn () => auth()->user()?->can('users.delete') ?? false)
                        ->after(fn (Collection $records) => $records->each(fn (User $record) => self::logAction('user.deleted', $record))),
                    Tables\Actions\RestoreBulkAction::make()
                        ->authorize(fn () => auth()->user()?->can('users.delete') ?? false)
                        ->after(fn (Collection $records) => $records->each(fn (User $record) => self::logAction('user.restored', $record))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('users.view') ?? false;
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(UserStatus::cases())
            ->mapWithKeys(fn (UserStatus $status) => [$status->value => ucfirst(strtolower($status->name))])
            ->all();
    }

    private static function bulkUpdateStatus(Collection $records, UserStatus $status): void
    {
        foreach ($records as $record) {
            if (! $record instanceof User) {
                continue;
            }

            $record->update(['status' => $status->value]);

            self::logAction(
                $status === UserStatus::ACTIVE ? 'user.activated' : 'user.deactivated',
                $record,
                [
                    'status' => $status->value,
                ]
            );
        }
    }

    public static function logAction(string $event, User $user, array $properties = []): void
    {
        $actor = auth()->user();
        $request = request();

        if (! $request) {
            return;
        }

        AuditLogger::record($actor, $event, $user, $request, array_merge([
            'roles' => $user->roles->pluck('name')->all(),
            'status' => $user->status instanceof UserStatus ? $user->status->value : $user->status,
        ], $properties));
    }
}
