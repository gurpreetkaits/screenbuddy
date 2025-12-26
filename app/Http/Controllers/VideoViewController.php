<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoViewController extends Controller
{
    /**
     * Record a video view.
     */
    public function recordView(Request $request, $id)
    {
        $request->validate([
            'watch_duration' => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean',
        ]);

        $video = Video::findOrFail($id);
        $userId = Auth::check() ? Auth::id() : null;
        $ipAddress = $request->ip();
        $userAgent = $request->header('User-Agent');

        // Check if this view already exists (within last hour to prevent spam)
        $existingView = VideoView::where('video_id', $video->id)
            ->where(function ($query) use ($userId, $ipAddress) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('ip_address', $ipAddress);
                }
            })
            ->where('viewed_at', '>', now()->subHour())
            ->first();

        if ($existingView) {
            // Update existing view with new watch duration
            $existingView->update([
                'watch_duration' => max($existingView->watch_duration, $request->input('watch_duration', 0)),
                'completed' => $request->input('completed', false) || $existingView->completed,
            ]);

            return response()->json([
                'message' => 'View updated',
                'view' => $existingView,
            ]);
        }

        // Create new view
        $view = VideoView::create([
            'video_id' => $video->id,
            'user_id' => $userId,
            'ip_address' => $userId ? null : $ipAddress, // Only store IP for anonymous users
            'user_agent' => $userAgent,
            'watch_duration' => $request->input('watch_duration', 0),
            'completed' => $request->input('completed', false),
            'viewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'View recorded',
            'view' => $view,
        ], 201);
    }

    /**
     * Get view statistics for a video.
     */
    public function getStats($id)
    {
        $video = Video::findOrFail($id);

        $totalViews = $video->views()->count();
        $uniqueViewers = $video->views()
            ->selectRaw('COUNT(DISTINCT COALESCE(user_id, ip_address)) as count')
            ->value('count');

        $authenticatedViews = $video->views()->whereNotNull('user_id')->count();
        $anonymousViews = $video->views()->whereNull('user_id')->count();

        $averageWatchDuration = $video->views()->avg('watch_duration');
        $completionRate = $video->views()->where('completed', true)->count() / max($totalViews, 1) * 100;

        // Recent viewers (authenticated only)
        $recentViewers = $video->views()
            ->whereNotNull('user_id')
            ->with('user:id,name')
            ->latest('viewed_at')
            ->limit(10)
            ->get()
            ->map(function ($view) {
                return [
                    'user_name' => $view->user->name ?? 'Unknown',
                    'viewed_at' => $view->viewed_at->toISOString(),
                    'watch_duration' => $view->watch_duration,
                    'completed' => $view->completed,
                ];
            });

        return response()->json([
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'authenticated_views' => $authenticatedViews,
            'anonymous_views' => $anonymousViews,
            'average_watch_duration' => round($averageWatchDuration, 2),
            'completion_rate' => round($completionRate, 2),
            'recent_viewers' => $recentViewers,
        ]);
    }
}
