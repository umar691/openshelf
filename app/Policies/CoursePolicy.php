<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return $course->instructor_id === $user->id || $user->hasRole('admin');
    }

    public function update(User $user, Course $course): bool
    {
        return $course->instructor_id === $user->id || $user->hasRole('admin');
    }

    public function delete(User $user, Course $course): bool
    {
        return $course->instructor_id === $user->id || $user->hasRole('admin');
    }
}
