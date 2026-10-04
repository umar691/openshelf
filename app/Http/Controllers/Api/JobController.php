<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyToJobRequest;
use App\Http\Requests\StoreJobPostRequest;
use App\Models\JobPost;
use App\Services\Jobs\JobApplicationService;
use App\Services\Jobs\JobPostService;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Request $request, SearchService $search): JsonResponse
    {
        $jobs = $search->apply(
            JobPost::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('closes_at')->orWhere('closes_at', '>', now())),
            $request->string('q')->value(),
        )
            ->when($request->string('location_type')->isNotEmpty(), fn ($query) => $query->where('location_type', $request->string('location_type')->value()))
            ->with('employer:id,name')
            ->latest()
            ->paginate(20);

        return response()->json($jobs);
    }

    public function store(StoreJobPostRequest $request, JobPostService $jobs): JsonResponse
    {
        $job = $jobs->createForEmployer($request->user(), $request->validated());

        return response()->json(['data' => $job], 201);
    }

    public function apply(
        ApplyToJobRequest $request,
        JobPost $job,
        JobApplicationService $applications,
    ): JsonResponse {
        $resumePath = $request->file('resume')?->store('private/resumes');
        $application = $applications->apply(
            $request->user(),
            $job,
            $request->validated('cover_letter'),
            $resumePath,
        );

        return response()->json(['data' => $application], 201);
    }

    public function publish(Request $request, JobPost $job): JsonResponse
    {
        Gate::authorize('update', $job);

        $job->update(['status' => 'published']);

        return response()->json(['data' => $job->fresh(), 'published' => true]);
    }
}
