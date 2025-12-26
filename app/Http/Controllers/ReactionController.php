<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReactionController extends Controller
{
    /**
     * Get reaction counts for a video.
     */
    public function index($videoId)
    {
        $video = Video::findOrFail($videoId);

        $counts = $video->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Include all reaction types with zero counts
        $allCounts = [];
        foreach (Reaction::TYPES as $type => $emoji) {
            $allCounts[$type] = [
                'count' => $counts[$type] ?? 0,
                'emoji' => $emoji,
            ];
        }

        return response()->json([
            'reactions' => $allCounts,
            'total' => array_sum($counts),
        ]);
    }

    /**
     * Add or toggle a reaction.
     */
    public function store(Request $request, $videoId)
    {
        $video = Video::findOrFail($videoId);

        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(Reaction::TYPES)),
            'session_id' => 'nullable|string|max:100',
        ]);

        $type = $validated['type'];

        // Check for existing reaction
        $existingQuery = Reaction::where('video_id', $video->id)
            ->where('type', $type);

        if (Auth::check()) {
            $existingQuery->where('user_id', Auth::id());
        } else {
            $sessionId = $validated['session_id'] ?? $request->ip();
            $existingQuery->where('session_id', $sessionId);
        }

        $existing = $existingQuery->first();

        if ($existing) {
            // Remove reaction (toggle off)
            $existing->delete();

            return response()->json([
                'message' => 'Reaction removed',
                'action' => 'removed',
                'type' => $type,
            ]);
        }

        // Add new reaction
        $reaction = new Reaction();
        $reaction->video_id = $video->id;
        $reaction->type = $type;

        if (Auth::check()) {
            $reaction->user_id = Auth::id();
        } else {
            $reaction->session_id = $validated['session_id'] ?? $request->ip();
        }

        $reaction->save();

        return response()->json([
            'message' => 'Reaction added',
            'action' => 'added',
            'type' => $type,
            'emoji' => Reaction::TYPES[$type],
        ], 201);
    }

    /**
     * Get user's reactions for a video.
     */
    public function userReactions(Request $request, $videoId)
    {
        $video = Video::findOrFail($videoId);

        $query = Reaction::where('video_id', $video->id);

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $sessionId = $request->query('session_id', $request->ip());
            $query->where('session_id', $sessionId);
        }

        $userReactions = $query->pluck('type')->toArray();

        return response()->json([
            'user_reactions' => $userReactions,
        ]);
    }

    /**
     * Get reactions for a shared video by token.
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
     * Add reaction to a shared video by token.
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
