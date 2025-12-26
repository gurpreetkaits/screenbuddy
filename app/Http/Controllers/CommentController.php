<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Get all comments for a video.
     */
    public function index($videoId)
    {
        $video = Video::findOrFail($videoId);

        $comments = $video->comments()
            ->with('user:id,name,avatar_url')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'author_name' => $comment->author_display_name,
                    'author_avatar' => $comment->user?->avatar_url,
                    'timestamp_seconds' => $comment->timestamp_seconds,
                    'created_at' => $comment->created_at,
                ];
            });

        return response()->json([
            'comments' => $comments,
        ]);
    }

    /**
     * Store a new comment.
     */
    public function store(Request $request, $videoId)
    {
        $video = Video::findOrFail($videoId);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
            'timestamp_seconds' => 'nullable|integer|min:0',
        ]);

        $comment = new Comment();
        $comment->video_id = $video->id;
        $comment->content = $validated['content'];
        $comment->timestamp_seconds = $validated['timestamp_seconds'] ?? null;

        // Use authenticated user if available, otherwise use provided name
        if (Auth::check()) {
            $comment->user_id = Auth::id();
        } else {
            $comment->author_name = $validated['author_name'] ?? 'Anonymous';
        }

        $comment->save();

        // Load user relationship for avatar
        $comment->load('user:id,name,avatar_url');

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'author_name' => $comment->author_display_name,
                'author_avatar' => $comment->user?->avatar_url,
                'timestamp_seconds' => $comment->timestamp_seconds,
                'created_at' => $comment->created_at,
            ],
        ], 201);
    }

    /**
     * Delete a comment.
     */
    public function destroy($videoId, $commentId)
    {
        $comment = Comment::where('video_id', $videoId)
            ->where('id', $commentId)
            ->firstOrFail();

        // For MVP, allow deletion without auth check
        // TODO: Add proper authorization when auth is implemented
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Get comments for a shared video by token.
     */
    public function indexByToken($token)
    {
        $video = Video::where('share_token', $token)->firstOrFail();

        if (!$video->isShareLinkValid()) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return $this->index($video->id);
    }

    /**
     * Add comment to a shared video by token.
     */
    public function storeByToken(Request $request, $token)
    {
        $video = Video::where('share_token', $token)->firstOrFail();

        if (!$video->isShareLinkValid()) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return $this->store($request, $video->id);
    }
}
