<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor', 'Author', 'Viewer']) || $user->can('content.view');
    }

    public function view(User $user, Content $content): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor', 'Author']) || $user->can('content.create');
    }

    public function update(User $user, Content $content): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->id === $content->author_id || $user->can('content.update');
    }

    public function delete(User $user, Content $content): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('content.delete');
    }
}
