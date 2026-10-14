<?php

namespace App\Policies;

use App\Models\Documents;
use App\Models\User;

class DocumentsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Documents $documents): bool
    {
        return $user->id === $documents->user_id;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, Documents $documents): bool
    {
        return $user->id === $documents->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Documents $documents): bool
    {
        return $user->id === $documents->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Documents $documents): bool
    {
        return $user->id === $documents->user_id;
    }
}
