<?php

namespace App\Services\Jobs;

use App\Models\JobPost;
use App\Models\User;
use Illuminate\Support\Str;

class JobPostService
{
    public function createForEmployer(User $employer, array $attributes): JobPost
    {
        $baseSlug = Str::slug($attributes['title']);
        $slug = $baseSlug;
        $suffix = 1;

        while (JobPost::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $employer->jobPosts()->create([
            ...$attributes,
            'slug' => $slug,
            'status' => 'draft',
        ]);
    }
}
