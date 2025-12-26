<?php

namespace App\Managers;

use App\Jobs\ConvertVideoToMp4;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;
use App\Repositories\VideoRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VideoManager
{
    public function __construct(
        protected VideoRepository $videos
    ) {}

    public function getUserVideos(int $userId): array
    {
        $videos = $this->videos->findByUserId($userId);
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        return $videos->map(function ($video) use ($frontendUrl) {
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
        })->toArray();
    }

    public function canUserRecordVideo(User $user): bool
    {
        return $user->canRecordVideo();
    }

    public function createVideo(User $user, array $data, UploadedFile $videoFile): Video
    {
        $video = $this->videos->createVideo([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration' => $data['duration'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);

        $video->addMedia($videoFile)->toMediaCollection('videos');

        $media = $video->getFirstMedia('videos');
        $originalExtension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $video->update(['original_extension' => strtolower($originalExtension)]);

        $video->generateThumbnailFromMidpoint();

        ConvertVideoToMp4::dispatch($video);

        $user->increment('videos_count');

        return $video;
    }

    public function getVideoDetails(Video $video): array
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream"),
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
        ];
    }

    public function getConversionStatus(Video $video): array
    {
        return [
            'conversion_status' => $video->conversion_status,
            'conversion_progress' => $video->conversion_progress,
            'conversion_error' => $video->conversion_error,
            'is_converting' => $video->isConverting(),
            'is_complete' => $video->isConversionComplete(),
            'is_failed' => $video->isConversionFailed(),
            'message' => $video->getConversionStatusMessage(),
            'converted_at' => $video->converted_at?->toISOString(),
        ];
    }

    public function updateVideo(Video $video, array $data): Video
    {
        $this->videos->updateVideo($video, $data);
        return $video->fresh();
    }

    public function deleteVideo(Video $video, User $user): void
    {
        $this->videos->deleteVideo($video);
        $user->decrement('videos_count');
    }

    public function toggleSharing(Video $video): Video
    {
        return $this->videos->togglePublicStatus($video);
    }

    public function regenerateShareToken(Video $video): Video
    {
        $video->regenerateShareToken();
        return $video;
    }

    public function getSharedVideoDetails(Video $video): ?array
    {
        if (!$video->isShareLinkValid()) {
            return null;
        }

        $reactionCounts = $this->videos->getReactionCounts($video);

        $reactions = [];
        foreach (Reaction::TYPES as $type => $emoji) {
            $reactions[$type] = [
                'count' => $reactionCounts[$type] ?? 0,
                'emoji' => $emoji,
            ];
        }

        $comments = $this->videos->getCommentsWithUser($video)->map(function ($comment) {
            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'author_name' => $comment->author_display_name,
                'author_avatar' => $comment->user?->avatar_url,
                'timestamp_seconds' => $comment->timestamp_seconds,
                'created_at' => $comment->created_at->toISOString(),
            ];
        });

        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream"),
            'thumbnail' => $video->getThumbnailUrl(),
            'created_at' => $video->created_at->toISOString(),
            'reactions' => $reactions,
            'comments' => $comments,
        ];
    }

    public function trimVideo(Video $video, float $startTime, float $endTime): array
    {
        $media = $video->getFirstMedia('videos');
        if (!$media) {
            throw new \Exception('Video file not found');
        }

        $newDuration = $endTime - $startTime;

        if ($endTime > $video->duration + 1) {
            throw new \InvalidArgumentException('End time exceeds video duration');
        }

        $originalPath = $media->getPath();
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $tempPath = storage_path('app/temp_trimmed_' . uniqid() . '.' . $extension);

        if (!is_dir(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        Log::info('Starting video trim', [
            'video_id' => $video->id,
            'original_path' => $originalPath,
            'temp_path' => $tempPath,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $newDuration,
        ]);

        $isWebM = strtolower($extension) === 'webm';
        $this->executeTrimCommand($originalPath, $tempPath, $startTime, $newDuration, $isWebM);

        $trimmedSize = filesize($tempPath);
        if ($trimmedSize < 1000) {
            @unlink($tempPath);
            throw new \Exception('Trimmed video file is invalid');
        }

        $video->clearMediaCollection('videos');
        $video->addMedia($tempPath)->toMediaCollection('videos');
        $video->duration = (int) round($newDuration);
        $video->save();
        $video->refresh();

        $video->generateThumbnailFromMidpoint();

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return [
            'id' => $video->id,
            'title' => $video->title,
            'duration' => $video->duration,
            'url' => url("/api/share/video/{$video->share_token}/stream") . '?v=' . time(),
            'thumbnail' => $video->getThumbnailUrl() . '?v=' . time(),
        ];
    }

    protected function executeTrimCommand(string $originalPath, string $tempPath, float $startTime, float $duration, bool $isWebM): void
    {
        if ($isWebM) {
            $ffmpegCommand = sprintf(
                'ffmpeg -y -i %s -ss %s -t %s -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus -b:a 128k %s 2>&1',
                escapeshellarg($originalPath),
                escapeshellarg(number_format($startTime, 3, '.', '')),
                escapeshellarg(number_format($duration, 3, '.', '')),
                escapeshellarg($tempPath)
            );
        } else {
            $ffmpegCommand = sprintf(
                'ffmpeg -y -ss %s -i %s -t %s -c copy -avoid_negative_ts make_zero %s 2>&1',
                escapeshellarg(number_format($startTime, 3, '.', '')),
                escapeshellarg($originalPath),
                escapeshellarg(number_format($duration, 3, '.', '')),
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
            if (!$isWebM) {
                $output = [];
                $ffmpegCommand = sprintf(
                    'ffmpeg -y -i %s -ss %s -t %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k %s 2>&1',
                    escapeshellarg($originalPath),
                    escapeshellarg(number_format($startTime, 3, '.', '')),
                    escapeshellarg(number_format($duration, 3, '.', '')),
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
                throw new \Exception('Failed to trim video: ' . implode(' ', array_slice($output, -3)));
            }
        }
    }

    public function streamVideo(Video $video, ?string $rangeHeader = null): array
    {
        $media = $video->getFirstMedia('videos');

        if (!$media) {
            throw new \Exception('Video file not found');
        }

        $filePath = $media->getPath();

        if (!file_exists($filePath)) {
            throw new \Exception('Video file not found on disk');
        }

        $fileSize = filesize($filePath);
        $mimeType = $media->mime_type;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $isWebM = $extension === 'webm' || $mimeType === 'video/webm';
        $isSmallFile = $fileSize < 50 * 1024 * 1024;

        return [
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'is_webm' => $isWebM,
            'is_small_file' => $isSmallFile,
            'range_header' => $rangeHeader,
        ];
    }

    public function canAccessSharedVideo(Video $video, ?int $userId = null): bool
    {
        $isOwner = $userId !== null && $userId === $video->user_id;
        return $isOwner || $video->isShareLinkValid();
    }

    public function findVideo(int $id): ?Video
    {
        return $this->videos->findWithMediaAndCounts($id);
    }

    public function findVideoOrFail(int $id): Video
    {
        return $this->videos->findOrFail($id);
    }

    public function findByShareToken(string $token): ?Video
    {
        return $this->videos->findByShareToken($token);
    }

    public function findByShareTokenOrFail(string $token): Video
    {
        return $this->videos->findByShareTokenOrFail($token);
    }
}
