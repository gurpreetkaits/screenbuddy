<?php

namespace App\Http\Controllers;

use App\Managers\VideoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    public function __construct(
        protected VideoManager $videoManager
    ) {}

    public function index(Request $request)
    {
        $videos = $this->videoManager->getUserVideos(Auth::id());

        return response()->json([
            'videos' => $videos,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->videoManager->canUserRecordVideo(Auth::user())) {
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
            'video' => 'required|file|mimes:webm,mp4,mov|max:10240000',
            'is_public' => 'nullable|boolean',
        ]);

        $video = $this->videoManager->createVideo(
            Auth::user(),
            $request->only(['title', 'description', 'duration', 'is_public']),
            $request->file('video')
        );

        return response()->json([
            'message' => 'Video uploaded successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"),
                'thumbnail' => $video->getThumbnailUrl(),
                'share_url' => $video->getShareUrl(),
                'is_public' => $video->is_public,
                'conversion_status' => $video->conversion_status,
                'created_at' => $video->created_at->toISOString(),
            ],
        ], 201);
    }

    public function show($id)
    {
        $video = $this->videoManager->findVideo($id);

        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'video' => $this->videoManager->getVideoDetails($video),
        ]);
    }

    public function conversionStatus($id)
    {
        $video = $this->videoManager->findVideoOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->videoManager->getConversionStatus($video));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $video = $this->videoManager->findVideoOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $video = $this->videoManager->updateVideo($video, $request->only(['title', 'description']));

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

    public function destroy($id)
    {
        $video = $this->videoManager->findVideoOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->videoManager->deleteVideo($video, Auth::user());

        return response()->json([
            'message' => 'Video deleted successfully',
        ]);
    }

    public function toggleSharing($id)
    {
        $video = $this->videoManager->findVideoOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $video = $this->videoManager->toggleSharing($video);

        return response()->json([
            'message' => $video->is_public ? 'Video is now public' : 'Video is now private',
            'is_public' => $video->is_public,
            'share_url' => $video->is_public ? $video->getShareUrl() : null,
        ]);
    }

    public function regenerateShareToken($id)
    {
        $video = $this->videoManager->findVideoOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $video = $this->videoManager->regenerateShareToken($video);

        return response()->json([
            'message' => 'Share link regenerated successfully',
            'share_url' => $video->getShareUrl(),
        ]);
    }

    public function stream($id)
    {
        $video = $this->videoManager->findVideoOrFail($id);

        try {
            $streamData = $this->videoManager->streamVideo($video, request()->header('Range'));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return $this->buildStreamResponse($streamData);
    }

    public function viewShared($token)
    {
        $video = $this->videoManager->findByShareTokenOrFail($token);

        $videoDetails = $this->videoManager->getSharedVideoDetails($video);

        if ($videoDetails === null) {
            return response()->json([
                'message' => 'This video is no longer available for sharing',
            ], 403);
        }

        return response()->json([
            'video' => $videoDetails,
        ]);
    }

    public function trim(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required|numeric|min:0',
            'end_time' => 'required|numeric|gt:start_time',
        ]);

        $video = $this->videoManager->findVideoOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->videoManager->trimVideo(
                $video,
                floatval($request->start_time),
                floatval($request->end_time)
            );

            return response()->json([
                'message' => 'Video trimmed successfully',
                'video' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function streamShared($token)
    {
        $video = $this->videoManager->findByShareTokenOrFail($token);

        if (!$this->videoManager->canAccessSharedVideo($video, Auth::id())) {
            return response()->json([
                'message' => 'This video is no longer available for sharing',
            ], 403);
        }

        try {
            $streamData = $this->videoManager->streamVideo($video, request()->header('Range'));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return $this->buildStreamResponse($streamData);
    }

    protected function buildStreamResponse(array $streamData): mixed
    {
        $filePath = $streamData['file_path'];
        $fileSize = $streamData['file_size'];
        $mimeType = $streamData['mime_type'];
        $isWebM = $streamData['is_webm'];
        $isSmallFile = $streamData['is_small_file'];
        $range = $streamData['range_header'];

        if ($isWebM || $isSmallFile) {
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

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

        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

        $maxChunkSize = 10 * 1024 * 1024;
        if (($end - $start + 1) > $maxChunkSize) {
            $end = $start + $maxChunkSize - 1;
        }

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
}
