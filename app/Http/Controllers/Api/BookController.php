<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\Marketplace\BookService;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookController extends Controller
{
    public function index(Request $request, SearchService $search): JsonResponse
    {
        $books = $search->apply(
            Book::query()->where('status', 'published'),
            $request->string('q')->value(),
        )
            ->with('vendor:id,name')
            ->withCount('reviews')
            ->latest('published_at')
            ->paginate(20);

        return response()->json($books);
    }

    public function show(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->status === 'published' || ($request->user() && Gate::allows('view', $book)), 404);

        return response()->json($book->load(['vendor:id,name', 'chapters' => fn ($query) => $query
            ->where('is_preview', true)
            ->select(['id', 'book_id', 'chapter_number', 'title', 'is_preview'])]));
    }

    public function store(StoreBookRequest $request, BookService $books): JsonResponse
    {
        $book = $books->createForVendor($request->user(), $request->validated());

        return response()->json(['data' => $book], 201);
    }

    public function publish(Request $request, Book $book): JsonResponse
    {
        Gate::authorize('update', $book);

        $book->update(['status' => 'published', 'published_at' => now()]);

        return response()->json(['data' => $book->fresh(), 'published' => true]);
    }
}
