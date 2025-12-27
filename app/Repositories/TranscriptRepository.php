<?php

namespace App\Repositories;

use App\Models\Transcript;

class TranscriptRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Transcript);
    }

    public function findByVideoId(int $videoId): ?Transcript
    {
        return Transcript::where('video_id', $videoId)->first();
    }

    public function findWithVideo(int $id): ?Transcript
    {
        return Transcript::with('video')->find($id);
    }

    public function createTranscript(array $data): Transcript
    {
        return Transcript::create($data);
    }

    public function updateTranscript(Transcript $transcript, array $data): bool
    {
        return $transcript->update($data);
    }

    public function markAsProcessing(Transcript $transcript): void
    {
        $transcript->markAsProcessing();
    }

    public function markAsCompleted(Transcript $transcript): void
    {
        $transcript->markAsCompleted();
    }

    public function markAsFailed(Transcript $transcript, string $errorMessage): void
    {
        $transcript->markAsFailed($errorMessage);
    }

    public function deleteTranscript(Transcript $transcript): bool
    {
        return $transcript->delete();
    }
}
