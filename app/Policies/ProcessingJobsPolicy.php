<?php

namespace App\Policies;

use App\Models\ProcessingJobs;
use App\Models\User;

class ProcessingJobsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model (progress).
     */
    public function view(User $user, ProcessingJobs $processingJobs): bool
    {
        return $user->id === $processingJobs->user_id;
    }

    /**
     * Determine whether the user can download the conversion result.
     */
    public function download(User $user, ProcessingJobs $processingJobs): bool
    {
        return $user->id === $processingJobs->user_id;
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
    public function update(User $user, ProcessingJobs $processingJobs): bool
    {
        return $user->id === $processingJobs->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProcessingJobs $processingJobs): bool
    {
        return $user->id === $processingJobs->user_id;
    }
}
