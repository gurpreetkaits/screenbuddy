<?php

namespace App\Repositories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

class VideoRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Video());
    }

    public function findByUserId(int $userId): Collection
    {
        return Video::with('media')
            ->where('user_id', $userId)
            ->latest()
            ->withCount(['views', 'comments', 'reactions'])
            ->get();
    }

    public function findWithMediaAndCounts(int $id): ?Video
    {
        return Video::with('media')
            ->withCount(['views', 'comments', 'reactions'])
            ->find($id);
    }

    public function findByShareToken(string $token): ?Video
    {
        return Video::where('share_token', $token)->first();
    }

    public function findByShareTokenOrFail(string $token): Video
    {
        return Video::where('share_token', $token)->firstOrFail();
    }

    public function createVideo(array $data): Video
    {
        return Video::create($data);
    }

    public function updateVideo(Video $video, array $data): bool
    {
        return $video->update($data);
    }

    public function deleteVideo(Video $video): bool
    {
        return $video->delete();
    }

    public function togglePublicStatus(Video $video): Video
    {
        $video->is_public = !$video->is_public;
        $video->save();
        return $video;
    }

    public function getReactionCounts(Video $video): array
    {
        return $video->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function getCommentsWithUser(Video $video): Collection
    {
        return $video->comments()
            ->with('user:id,name,avatar_url')
            ->get();
    }
}
