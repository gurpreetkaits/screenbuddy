<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

class CommentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Comment());
    }

    public function getVideoComments(Video $video): Collection
    {
        return $video->comments()
            ->with('user:id,name,avatar_url')
            ->get();
    }

    public function createComment(array $data): Comment
    {
        $comment = new Comment();
        $comment->video_id = $data['video_id'];
        $comment->content = $data['content'];
        $comment->timestamp_seconds = $data['timestamp_seconds'] ?? null;

        if (isset($data['user_id'])) {
            $comment->user_id = $data['user_id'];
        } else {
            $comment->author_name = $data['author_name'] ?? 'Anonymous';
        }

        $comment->save();
        $comment->load('user:id,name,avatar_url');

        return $comment;
    }

    public function findByVideoAndId(int $videoId, int $commentId): ?Comment
    {
        return Comment::where('video_id', $videoId)
            ->where('id', $commentId)
            ->first();
    }

    public function deleteComment(Comment $comment): bool
    {
        return $comment->delete();
    }
}
