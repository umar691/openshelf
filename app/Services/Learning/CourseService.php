<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    public function createForInstructor(User $instructor, array $attributes): Course
    {
        return DB::transaction(function () use ($instructor, $attributes): Course {
            $baseSlug = Str::slug($attributes['title']);
            $slug = $baseSlug;
            $suffix = 1;

            while (Course::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            return $instructor->courses()->create([
                ...$attributes,
                'slug' => $slug,
                'status' => 'draft',
            ]);
        });
    }
}
