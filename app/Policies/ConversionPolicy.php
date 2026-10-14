<?php

namespace App\Policies;

use App\Models\Conversion;
use App\Models\User;

class ConversionPolicy
{
    /**
     * Determine whether the user can view any conversions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific conversion.
     */
    public function view(User $user, Conversion $conversion): bool
    {
        return $user->id === $conversion->user_id;
    }

    /**
     * Determine whether the user can download the converted file.
     */
    public function download(User $user, Conversion $conversion): bool
    {
        return $user->id === $conversion->user_id;
    }

    /**
     * Determine whether the user can create conversions.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the conversion.
     */
    public function delete(User $user, Conversion $conversion): bool
    {
        return $user->id === $conversion->user_id;
    }
}
