<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommunityController extends Controller
{
    public function comments(Content $content): JsonResponse
    {
        return response()->json(
            $content->comments()->with('author:id,name,avatar_url')->limit(50)->get()
        );
    }

    public function storeComment(Request $request, Content $content): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $comment = Comment::create([
            ...$data,
            'content_id' => $content->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($comment->load('author:id,name,avatar_url'), 201);
    }

    public function messages(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->is($user), 422, 'Choose another member to start a conversation.');

        $messages = Message::query()
            ->where(fn ($query) => $query->where('sender_id', $request->user()->id)->where('recipient_id', $user->id))
            ->orWhere(fn ($query) => $query->where('sender_id', $user->id)->where('recipient_id', $request->user()->id))
            ->with('sender:id,name')
            ->oldest()
            ->limit(100)
            ->get();

        Message::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['member' => $user->only(['id', 'name', 'headline']), 'messages' => $messages]);
    }

    public function storeMessage(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['recipient' => 'Choose another member to send a message.']);
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $message = Message::create([
            ...$data,
            'sender_id' => $request->user()->id,
            'recipient_id' => $user->id,
        ]);

        return response()->json($message->load('sender:id,name'), 201);
    }
}
