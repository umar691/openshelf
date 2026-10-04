<?php

namespace App\Policies;

use App\Models\JobPost;
use App\Models\User;

class JobPostPolicy
{
    public function view(User $user, JobPost $job): bool
    {
        return $job->employer_id === $user->id || $user->hasRole('admin');
    }

    public function update(User $user, JobPost $job): bool
    {
        return $job->employer_id === $user->id || $user->hasRole('admin');
    }

    public function delete(User $user, JobPost $job): bool
    {
        return $job->employer_id === $user->id || $user->hasRole('admin');
    }
}
