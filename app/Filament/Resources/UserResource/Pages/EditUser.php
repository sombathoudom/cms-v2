<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string, mixed> */
    protected array $originalAttributes = [];

    /** @var list<string> */
    protected array $originalRoles = [];

    public function mount($record): void
    {
        parent::mount($record);

        $this->originalAttributes = [
            'name' => $this->record->name,
            'email' => $this->record->email,
            'status' => $this->record->status instanceof UserStatus ? $this->record->status->value : $this->record->status,
        ];

        $this->originalRoles = $this->record->roles->pluck('name')->sort()->values()->all();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterSave(): void
    {
        $currentAttributes = [
            'name' => $this->record->name,
            'email' => $this->record->email,
            'status' => $this->record->status instanceof UserStatus ? $this->record->status->value : $this->record->status,
        ];

        $changedAttributes = collect($currentAttributes)
            ->filter(fn ($value, $key) => $this->originalAttributes[$key] !== $value)
            ->keys()
            ->all();

        $currentRoles = $this->record->roles->pluck('name')->sort()->values()->all();
        $rolesChanged = $currentRoles !== $this->originalRoles;

        if ($rolesChanged) {
            $changedAttributes[] = 'roles';
        }

        if (! empty($changedAttributes)) {
            UserResource::logAction('user.updated', $this->record, [
                'changed' => array_values(array_unique($changedAttributes)),
            ]);
        }

        $this->originalAttributes = $currentAttributes;
        $this->originalRoles = $currentRoles;
    }

    protected function afterDelete(): void
    {
        UserResource::logAction('user.deleted', $this->record);
    }

    protected function afterRestore(): void
    {
        UserResource::logAction('user.restored', $this->record);
    }
}
