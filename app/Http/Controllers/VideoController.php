<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertVideoToMp4;
use App\Models\Reaction;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    /**
     * Display a listing of the user's videos.
     */
    public function index(Request $request)
    {
        // Get videos for the authenticated user only
        // Eager load media to avoid N+1 queries (thumbnails are in same media table)
        $videos = Video::with('media')
            ->where('user_id', Auth::id())
            ->latest()
            ->withCount(['views', 'comments', 'reactions'])
            ->get();

        // Pre-compute frontend URL once
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        // Map videos efficiently - avoid method calls that query DB
        $mappedVideos = $videos->map(function ($video) use ($frontendUrl) {
            // Get thumbnail from already-loaded media collection (no extra query)
            $thumbnail = $video->media->where('collection_name', 'thumbnails')->first();
            $thumbnailUrl = $thumbnail ? $thumbnail->getUrl() : null;

            return [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"),
                'thumbnail' => $thumbnailUrl,
                'share_url' => "{$frontendUrl}/share/video/{$video->share_token}",
                'is_public' => $video->is_public,
                'views_count' => $video->views_count ?? 0,
                'comments_count' => $video->comments_count ?? 0,
                'reactions_count' => $video->reactions_count ?? 0,
                'conversion_status' => $video->conversion_status,
                'conversion_progress' => $video->conversion_progress,
                'is_converting' => in_array($video->conversion_status, ['pending', 'processing']),
                'created_at' => $video->created_at->toISOString(),
                'updated_at' => $video->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'videos' => $mappedVideos,
        ]);
    }

    /**
     * Store a newly created video.
     */
    public function store(Request $request)
    {
        // Double-check subscription limit (middleware also checks this)
        if (!Auth::user()->canRecordVideo()) {
            return response()->json([
                'error' => 'video_limit_reached',
                'message' => 'You have reached your video limit. Upgrade to Pro to continue recording.',
                'videos_count' => Auth::user()->getVideosCount(),
                'remaining_quota' => Auth::user()->getRemainingVideoQuota(),
                'upgrade_url' => config('services.frontend.url') . '/subscription',
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'video' => 'required|file|mimes:webm,mp4,mov|max:10240000', // Max 10GB (supports 1+ hour recordings)
            'is_public' => 'nullable|boolean',
        ]);

        $video = Video::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'duration' => $request->duration,
            'is_public' => $request->input('is_public', true), // Default to public for easy sharing
        ]);

        // Add the video file using Spatie Media Library
        $video->addMediaFromRequest('video')
            ->toMediaCollection('videos');

        // Store original extension for reference
        $media = $video->getFirstMedia('videos');
        $originalExtension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $video->update(['original_extension' => strtolower($originalExtension)]);

        // Generate thumbnail from the midpoint of the video
        $video->generateThumbnailFromMidpoint();

        // Dispatch background job to convert to MP4 with faststart for instant seeking
        // Skip if already MP4 (will still add faststart if needed)
        ConvertVideoToMp4::dispatch($video);

        // Increment user's video count
        Auth::user()->increment('videos_count');

        return response()->json([
            'message' => 'Video uploaded successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"), // Use public streaming endpoint
                'thumbnail' => $video->getThumbnailUrl(),
                'share_url' => $video->getShareUrl(),
                'is_public' => $video->is_public,
                'conversion_status' => $video->conversion_status,
                'created_at' => $video->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Display the specified video.
     */
    public function show($id)
    {
        $video = Video::with('media')->withCount(['views', 'comments', 'reactions'])->findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $media = $video->getFirstMedia('videos');

        return response()->json([
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"), // Use public streaming endpoint
                'thumbnail' => $video->getThumbnailUrl(),
                'share_url' => $video->getShareUrl(),
                'is_public' => $video->is_public,
                'views_count' => $video->views_count ?? 0,
                'comments_count' => $video->comments_count ?? 0,
                'reactions_count' => $video->reactions_count ?? 0,
                'conversion_status' => $video->conversion_status,
                'conversion_progress' => $video->conversion_progress,
                'is_converting' => $video->isConverting(),
                'created_at' => $video->created_at->toISOString(),
                'updated_at' => $video->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get the conversion status for a video.
     */
    public function conversionStatus($id)
    {
        $video = Video::findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'conversion_status' => $video->conversion_status,
            'conversion_progress' => $video->conversion_progress,
            'conversion_error' => $video->conversion_error,
            'is_converting' => $video->isConverting(),
            'is_complete' => $video->isConversionComplete(),
            'is_failed' => $video->isConversionFailed(),
            'message' => $video->getConversionStatusMessage(),
            'converted_at' => $video->converted_at?->toISOString(),
        ]);
    }

    /**
     * Update the specified video.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $video = Video::findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $video->update($request->only(['title', 'description']));

        return response()->json([
            'message' => 'Video updated successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'updated_at' => $video->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Remove the specified video.
     */
    public function destroy($id)
    {
        $video = Video::findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Spatie Media Library will automatically delete associated media files
        $video->delete();

        // Decrement user's video count
        Auth::user()->decrement('videos_count');

        return response()->json([
            'message' => 'Video deleted successfully',
        ]);
    }

    /**
     * Toggle video public sharing status.
     */
    public function toggleSharing($id)
    {
        $video = Video::findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $video->is_public = !$video->is_public;
        $video->save();

        return response()->json([
            'message' => $video->is_public ? 'Video is now public' : 'Video is now private',
            'is_public' => $video->is_public,
            'share_url' => $video->is_public ? $video->getShareUrl() : null,
        ]);
    }

    /**
     * Regenerate share token for a video.
     */
    public function regenerateShareToken($id)
    {
        $video = Video::findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $video->regenerateShareToken();

        return response()->json([
            'message' => 'Share link regenerated successfully',
            'share_url' => $video->getShareUrl(),
        ]);
    }

    /**
     * Stream video file with Range request support (for instant seeking).
     */
    public function stream($id)
    {
        $video = Video::findOrFail($id);
        $media = $video->getFirstMedia('videos');

        if (!$media) {
            return response()->json(['message' => 'Video file not found'], 404);
        }

        $filePath = $media->getPath();

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Video file not found on disk'], 404);
        }

        $fileSize = filesize($filePath);
        $mimeType = $media->mime_type;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // WebM files have metadata at the END of the file, so partial streaming breaks playback.
        // For WebM files (or small files <50MB), serve the full file to ensure playback works.
        $isWebM = $extension === 'webm' || $mimeType === 'video/webm';
        $isSmallFile = $fileSize < 50 * 1024 * 1024; // 50MB

        if ($isWebM || $isSmallFile) {
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        // For large MP4 files, use range request streaming
        $range = request()->header('Range');

        if (!$range) {
            $start = 0;
            $end = min(2 * 1024 * 1024, $fileSize - 1);
            $length = $end - $start + 1;

            $file = fopen($filePath, 'rb');
            fseek($file, $start);
            $data = fread($file, $length);
            fclose($file);

            return response($data, 206)
                ->header('Content-Type', $mimeType)
                ->header('Content-Length', $length)
                ->header('Content-Range', "bytes $start-$end/$fileSize")
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, max-age=31536000');
        }

        // Parse Range header (e.g., "bytes=0-1023")
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

        // Limit chunk size to 10MB to prevent memory issues
        $maxChunkSize = 10 * 1024 * 1024;
        if (($end - $start + 1) > $maxChunkSize) {
            $end = $start + $maxChunkSize - 1;
        }

        $length = $end - $start + 1;

        // Open file and seek to start position
        $file = fopen($filePath, 'rb');
        fseek($file, $start);
        $data = fread($file, $length);
        fclose($file);

        // Return partial content with 206 status
        return response($data, 206)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', $length)
            ->header('Content-Range', "bytes $start-$end/$fileSize")
            ->header('Accept-Ranges', 'bytes')
            ->header('Cache-Control', 'public, max-age=31536000');
    }

    /**
     * View a public video via share token.
     */
    public function viewShared($token)
    {
        $video = Video::where('share_token', $token)->firstOrFail();

        // Check if the share link is valid
        if (!$video->isShareLinkValid()) {
            return response()->json([
                'message' => 'This video is no longer available for sharing',
            ], 403);
        }

        $media = $video->getFirstMedia('videos');

        // Get reaction counts
        $reactionCounts = $video->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $reactions = [];
        foreach (Reaction::TYPES as $type => $emoji) {
            $reactions[$type] = [
                'count' => $reactionCounts[$type] ?? 0,
                'emoji' => $emoji,
            ];
        }

        // Get comments
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
                    'created_at' => $comment->created_at->toISOString(),
                ];
            });

        return response()->json([
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$token}/stream"), // Use public streaming endpoint
                'thumbnail' => $video->getThumbnailUrl(),
                'created_at' => $video->created_at->toISOString(),
                'reactions' => $reactions,
                'comments' => $comments,
            ],
        ]);
    }

    /**
     * Trim a video to specified start and end times.
     */
    public function trim(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required|numeric|min:0',
            'end_time' => 'required|numeric|gt:start_time',
        ]);

        $video = Video::findOrFail($id);

        // Ensure the user owns this video
        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $media = $video->getFirstMedia('videos');
        if (!$media) {
            return response()->json(['message' => 'Video file not found'], 404);
        }

        $startTime = floatval($request->start_time);
        $endTime = floatval($request->end_time);
        $newDuration = $endTime - $startTime;

        // Validate times against video duration
        if ($endTime > $video->duration + 1) { // Allow 1 second tolerance
            return response()->json(['message' => 'End time exceeds video duration'], 422);
        }

        $originalPath = $media->getPath();
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $tempPath = storage_path('app/temp_trimmed_' . uniqid() . '.' . $extension);

        // Ensure temp directory exists
        if (!is_dir(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        Log::info('Starting video trim', [
            'video_id' => $id,
            'original_path' => $originalPath,
            'temp_path' => $tempPath,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $newDuration,
        ]);

        // For WebM files, we need to re-encode because VP8/VP9 doesn't support accurate seeking with stream copy
        // Use -ss after -i for accurate seeking (slower but precise)
        $isWebM = strtolower($extension) === 'webm';

        if ($isWebM) {
            // WebM: Re-encode with VP9/Opus for accurate trimming
            $ffmpegCommand = sprintf(
                'ffmpeg -y -i %s -ss %s -t %s -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus -b:a 128k %s 2>&1',
                escapeshellarg($originalPath),
                escapeshellarg(number_format($startTime, 3, '.', '')),
                escapeshellarg(number_format($newDuration, 3, '.', '')),
                escapeshellarg($tempPath)
            );
        } else {
            // MP4/MOV: Try stream copy first (fast), fall back to re-encode
            $ffmpegCommand = sprintf(
                'ffmpeg -y -ss %s -i %s -t %s -c copy -avoid_negative_ts make_zero %s 2>&1',
                escapeshellarg(number_format($startTime, 3, '.', '')),
                escapeshellarg($originalPath),
                escapeshellarg(number_format($newDuration, 3, '.', '')),
                escapeshellarg($tempPath)
            );
        }

        Log::info('FFmpeg command', ['command' => $ffmpegCommand]);

        exec($ffmpegCommand, $output, $returnCode);

        Log::info('FFmpeg result', [
            'return_code' => $returnCode,
            'output' => implode("\n", $output),
            'file_exists' => file_exists($tempPath),
        ]);

        if ($returnCode !== 0 || !file_exists($tempPath)) {
            // If stream copy fails for non-WebM, try re-encoding with H.264
            if (!$isWebM) {
                $output = [];
                $ffmpegCommand = sprintf(
                    'ffmpeg -y -i %s -ss %s -t %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k %s 2>&1',
                    escapeshellarg($originalPath),
                    escapeshellarg(number_format($startTime, 3, '.', '')),
                    escapeshellarg(number_format($newDuration, 3, '.', '')),
                    escapeshellarg($tempPath)
                );

                Log::info('FFmpeg fallback command', ['command' => $ffmpegCommand]);

                exec($ffmpegCommand, $output, $returnCode);

                Log::info('FFmpeg fallback result', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output),
                    'file_exists' => file_exists($tempPath),
                ]);
            }

            if ($returnCode !== 0 || !file_exists($tempPath)) {
                Log::error('FFmpeg trim failed', [
                    'command' => $ffmpegCommand,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode,
                ]);
                return response()->json(['message' => 'Failed to trim video: ' . implode(' ', array_slice($output, -3))], 500);
            }
        }

        // Verify trimmed file size
        $trimmedSize = filesize($tempPath);
        Log::info('Trimmed file created', [
            'temp_path' => $tempPath,
            'size' => $trimmedSize,
        ]);

        if ($trimmedSize < 1000) {
            Log::error('Trimmed file too small', ['size' => $trimmedSize]);
            @unlink($tempPath);
            return response()->json(['message' => 'Trimmed video file is invalid'], 500);
        }

        // Delete old media and add the trimmed video
        $video->clearMediaCollection('videos');
        $video->addMedia($tempPath)
            ->toMediaCollection('videos');

        // Update duration
        $video->duration = (int) round($newDuration);
        $video->save();

        // Refresh to get new media
        $video->refresh();

        Log::info('Video trim completed', [
            'video_id' => $video->id,
            'new_duration' => $video->duration,
            'new_media' => $video->getFirstMedia('videos')?->getPath(),
        ]);

        // Regenerate thumbnail from the new video's midpoint
        $video->generateThumbnailFromMidpoint();

        // Clean up temp file if it still exists
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return response()->json([
            'message' => 'Video trimmed successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream") . '?v=' . time(),
                'thumbnail' => $video->getThumbnailUrl() . '?v=' . time(),
            ],
        ]);
    }

    /**
     * Stream a shared video file (public for shared videos, or owner can access their own).
     */
    public function streamShared($token)
    {
        $video = Video::where('share_token', $token)->firstOrFail();

        // Allow access if:
        // 1. The share link is valid (public video, not expired), OR
        // 2. The authenticated user is the owner
        $isOwner = Auth::check() && Auth::id() === $video->user_id;

        if (!$isOwner && !$video->isShareLinkValid()) {
            return response()->json([
                'message' => 'This video is no longer available for sharing',
            ], 403);
        }

        $media = $video->getFirstMedia('videos');

        if (!$media) {
            return response()->json(['message' => 'Video file not found'], 404);
        }

        $filePath = $media->getPath();

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Video file not found on disk'], 404);
        }

        $fileSize = filesize($filePath);
        $mimeType = $media->mime_type;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // WebM files have metadata at the END of the file, so partial streaming breaks playback.
        // For WebM files (or small files <50MB), serve the full file to ensure playback works.
        // MP4 files with faststart have metadata at the beginning, so streaming works fine.
        $isWebM = $extension === 'webm' || $mimeType === 'video/webm';
        $isSmallFile = $fileSize < 50 * 1024 * 1024; // 50MB

        if ($isWebM || $isSmallFile) {
            // Serve full file for WebM or small files - ensures playback works
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        // For large MP4 files, use range request streaming for efficiency
        $range = request()->header('Range');

        if (!$range) {
            // No range request - send first chunk to trigger proper range request
            $start = 0;
            $end = min(2 * 1024 * 1024, $fileSize - 1); // Send first 2MB
            $length = $end - $start + 1;

            $file = fopen($filePath, 'rb');
            fseek($file, $start);
            $data = fread($file, $length);
            fclose($file);

            return response($data, 206)
                ->header('Content-Type', $mimeType)
                ->header('Content-Length', $length)
                ->header('Content-Range', "bytes $start-$end/$fileSize")
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, max-age=31536000');
        }

        // Parse Range header (e.g., "bytes=0-1023")
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

        // Limit chunk size to 10MB to prevent memory issues
        $maxChunkSize = 10 * 1024 * 1024;
        if (($end - $start + 1) > $maxChunkSize) {
            $end = $start + $maxChunkSize - 1;
        }

        $length = $end - $start + 1;

        // Open file and seek to start position
        $file = fopen($filePath, 'rb');
        fseek($file, $start);
        $data = fread($file, $length);
        fclose($file);

        // Return partial content with 206 status
        return response($data, 206)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', $length)
            ->header('Content-Range', "bytes $start-$end/$fileSize")
            ->header('Accept-Ranges', 'bytes')
            ->header('Cache-Control', 'public, max-age=31536000');
    }
}
