<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $contents = Content::published()
            ->with('author:id,name,headline,avatar_url')
            ->withCount(['comments', 'reactions'])
            ->latest('published_at')
            ->limit(36)
            ->get();

        if ($user) {
            $drafts = Content::query()
                ->where('user_id', $user->id)
                ->where('status', 'draft')
                ->with('author:id,name,headline,avatar_url')
                ->withCount(['comments', 'reactions'])
                ->latest()
                ->get();
            $contents = $contents->merge($drafts);
        }

        return view('home', [
            'contents' => $contents,
            'members' => User::query()->select(['id', 'name', 'headline', 'avatar_url'])
                ->when($user, fn ($query) => $query->whereKeyNot($user->id))
                ->latest('id')->limit(12)->get(),
            'stats' => [
                'members' => User::count(),
                'library' => Content::published()->count(),
                'research' => Content::published()->where('type', 'research')->count(),
            ],
            'savedIds' => $user ? $user->bookmarks()->pluck('contents.id')->all() : [],
            'reactedIds' => $user ? $user->reactions()->pluck('contents.id')->all() : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['novel', 'post', 'research', 'resource'])],
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'topic' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::in(['published', 'draft'])],
        ]);

        $data['tags'] = collect(explode(',', $data['tags'] ?? ''))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->take(8)
            ->values()
            ->all();
        $data['user_id'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        Content::create($data);

        return redirect()->route('home')->with('status', 'Your work is now in your library.');
    }

    public function bookmark(Request $request, Content $content): JsonResponse
    {
        $user = $request->user();
        $saved = $user->bookmarks()->toggle($content->id)['attached'] !== [];

        return response()->json(['saved' => $saved]);
    }

    public function publish(Request $request, Content $content): JsonResponse
    {
        abort_unless($content->user_id === $request->user()->id, 403);

        if ($content->status === 'draft') {
            $content->update(['status' => 'published', 'published_at' => now()]);
        }

        return response()->json(['published' => true]);
    }

    public function react(Request $request, Content $content): JsonResponse
    {
        $user = $request->user();
        $reacted = $user->reactions()->toggle($content->id)['attached'] !== [];

        return response()->json([
            'reacted' => $reacted,
            'count' => $content->reactions()->count(),
        ]);
    }
}
