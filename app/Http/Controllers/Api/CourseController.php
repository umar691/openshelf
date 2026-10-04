<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use App\Services\Learning\CourseService;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function index(Request $request, SearchService $search): JsonResponse
    {
        $courses = $search->apply(
            Course::query()->where('status', 'published'),
            $request->string('q')->value(),
        )
            ->with('instructor:id,name')
            ->latest('published_at')
            ->paginate(20);

        return response()->json($courses);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        abort_unless($course->status === 'published' || ($request->user() && Gate::allows('view', $course)), 404);

        return response()->json($course->load([
            'instructor:id,name',
            'modules.lessons' => fn ($query) => $query
                ->where('is_published', true)
                ->select(['id', 'course_module_id', 'position', 'title', 'duration_seconds', 'is_preview']),
        ]));
    }

    public function store(StoreCourseRequest $request, CourseService $courses): JsonResponse
    {
        $course = $courses->createForInstructor($request->user(), $request->validated());

        return response()->json(['data' => $course], 201);
    }

    public function publish(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        $course->update(['status' => 'published', 'published_at' => now()]);

        return response()->json(['data' => $course->fresh(), 'published' => true]);
    }
}
