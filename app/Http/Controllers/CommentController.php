<?php

namespace App\Http\Controllers;

use App\Managers\CommentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        protected CommentManager $commentManager
    ) {}

    public function index($videoId)
    {
        $comments = $this->commentManager->getVideoComments($videoId);

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, $videoId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
            'timestamp_seconds' => 'nullable|integer|min:0',
        ]);

        $comment = $this->commentManager->createComment(
            $videoId,
            $validated,
            Auth::id()
        );

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $this->commentManager->formatComment($comment),
        ], 201);
    }

    public function destroy($videoId, $commentId)
    {
        $deleted = $this->commentManager->deleteComment($videoId, $commentId);

        if (!$deleted) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    public function indexByToken($token)
    {
        $comments = $this->commentManager->getSharedVideoComments($token);

        if ($comments === null) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function storeByToken(Request $request, $token)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
            'timestamp_seconds' => 'nullable|integer|min:0',
        ]);

        $comment = $this->commentManager->createSharedVideoComment(
            $token,
            $validated,
            Auth::id()
        );

        if ($comment === null) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $this->commentManager->formatComment($comment),
        ], 201);
    }
}
