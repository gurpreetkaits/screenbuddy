<?php

namespace App\Managers;

use App\Models\Comment;
use App\Models\Video;
use App\Repositories\CommentRepository;
use App\Repositories\VideoRepository;

class CommentManager
{
    public function __construct(
        protected CommentRepository $comments,
        protected VideoRepository $videos
    ) {}

    public function getVideoComments(int $videoId): array
    {
        $video = $this->videos->findOrFail($videoId);

        return $this->comments->getVideoComments($video)
            ->map(function ($comment) {
                return $this->formatComment($comment);
            })
            ->toArray();
    }

    public function getSharedVideoComments(string $token): ?array
    {
        $video = $this->videos->findByShareToken($token);

        if (!$video || !$video->isShareLinkValid()) {
            return null;
        }

        return $this->getVideoComments($video->id);
    }

    public function createComment(int $videoId, array $data, ?int $userId = null): Comment
    {
        $video = $this->videos->findOrFail($videoId);

        $commentData = [
            'video_id' => $video->id,
            'content' => $data['content'],
            'timestamp_seconds' => $data['timestamp_seconds'] ?? null,
        ];

        if ($userId) {
            $commentData['user_id'] = $userId;
        } else {
            $commentData['author_name'] = $data['author_name'] ?? 'Anonymous';
        }

        return $this->comments->createComment($commentData);
    }

    public function createSharedVideoComment(string $token, array $data, ?int $userId = null): ?Comment
    {
        $video = $this->videos->findByShareToken($token);

        if (!$video || !$video->isShareLinkValid()) {
            return null;
        }

        return $this->createComment($video->id, $data, $userId);
    }

    public function deleteComment(int $videoId, int $commentId): bool
    {
        $comment = $this->comments->findByVideoAndId($videoId, $commentId);

        if (!$comment) {
            return false;
        }

        return $this->comments->deleteComment($comment);
    }

    public function formatComment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'author_name' => $comment->author_display_name,
            'author_avatar' => $comment->user?->avatar_url,
            'timestamp_seconds' => $comment->timestamp_seconds,
            'created_at' => $comment->created_at,
        ];
    }
}
