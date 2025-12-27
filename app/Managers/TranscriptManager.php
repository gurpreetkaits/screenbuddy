<?php

namespace App\Managers;

use App\Data\TranscriptData;
use App\Jobs\TranscribeVideoJob;
use App\Models\Transcript;
use App\Models\Video;
use App\Repositories\TranscriptRepository;

class TranscriptManager
{
    public function __construct(
        protected TranscriptRepository $transcripts
    ) {}

    public function getTranscriptByVideoId(int $videoId): ?TranscriptData
    {
        $transcript = $this->transcripts->findByVideoId($videoId);

        if (! $transcript) {
            return null;
        }

        return TranscriptData::from($transcript);
    }

    public function createTranscript(Video $video): Transcript
    {
        $transcript = $this->transcripts->createTranscript([
            'video_id' => $video->id,
            'status' => 'pending',
        ]);

        TranscribeVideoJob::dispatch($transcript);

        return $transcript;
    }

    public function retryTranscription(int $transcriptId): bool
    {
        $transcript = $this->transcripts->findOrFail($transcriptId);

        if ($transcript->status === 'processing') {
            return false;
        }

        $this->transcripts->updateTranscript($transcript, [
            'status' => 'pending',
            'error_message' => null,
        ]);

        TranscribeVideoJob::dispatch($transcript);

        return true;
    }

    public function updateTranscriptData(
        Transcript $transcript,
        string $language,
        array $segments,
        string $fullText
    ): void {
        $segmentCollection = collect($segments)->map(fn ($segment, $index) => [
            'id' => $index,
            'start' => $segment['start'],
            'end' => $segment['end'],
            'text' => $segment['text'],
        ])->toArray();

        $this->transcripts->updateTranscript($transcript, [
            'language' => $language,
            'segments' => $segmentCollection,
            'full_text' => $fullText,
        ]);
    }

    public function markAsProcessing(Transcript $transcript): void
    {
        $this->transcripts->markAsProcessing($transcript);
    }

    public function markAsCompleted(Transcript $transcript): void
    {
        $this->transcripts->markAsCompleted($transcript);
    }

    public function markAsFailed(Transcript $transcript, string $errorMessage): void
    {
        $this->transcripts->markAsFailed($transcript, $errorMessage);
    }

    public function deleteTranscript(Transcript $transcript): bool
    {
        return $this->transcripts->deleteTranscript($transcript);
    }
}
