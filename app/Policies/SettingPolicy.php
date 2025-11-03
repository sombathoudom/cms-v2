<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('settings.view');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->can('settings.create');
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasRole('Admin') || $user->can('settings.update');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->hasRole('Admin') || $user->can('settings.delete');
    }
}
