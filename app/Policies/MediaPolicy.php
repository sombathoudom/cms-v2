<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor', 'Author', 'Viewer']) || $user->can('media.view');
    }

    public function view(User $user, Media $media): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor', 'Author']) || $user->can('media.create');
    }

    public function update(User $user, Media $media): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->id === $media->uploaded_by || $user->can('media.update');
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->hasAnyRole(['Admin', 'Editor']) || $user->can('media.delete');
    }
}
