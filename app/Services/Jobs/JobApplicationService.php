<?php

namespace App\Services\Jobs;

use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobApplicationService
{
    public function apply(User $applicant, JobPost $job, string $coverLetter, ?string $resumePath): JobApplication
    {
        return DB::transaction(function () use ($applicant, $job, $coverLetter, $resumePath): JobApplication {
            $job = JobPost::query()->lockForUpdate()->findOrFail($job->id);

            if ($job->status !== 'published' || ($job->closes_at && $job->closes_at->isPast())) {
                throw ValidationException::withMessages(['job' => 'This job is no longer accepting applications.']);
            }

            if ($job->employer_id === $applicant->id) {
                throw ValidationException::withMessages(['job' => 'You cannot apply to your own job post.']);
            }

            if ($job->applications()->where('applicant_id', $applicant->id)->exists()) {
                throw ValidationException::withMessages(['job' => 'You have already applied to this job.']);
            }

            return $job->applications()->create([
                'applicant_id' => $applicant->id,
                'cover_letter' => $coverLetter,
                'resume_path' => $resumePath,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });
    }
}
