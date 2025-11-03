<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor', 'Author', 'Viewer']) || $user->can('taxonomy.view');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('taxonomy.create');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('taxonomy.update');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasAnyRole(['Admin']) || $user->can('taxonomy.delete');
    }
}
