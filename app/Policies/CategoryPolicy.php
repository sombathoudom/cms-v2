<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor', 'Author', 'Viewer']) || $user->can('taxonomy.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('taxonomy.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('taxonomy.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasAnyRole(['Admin']) || $user->can('taxonomy.delete');
    }
}
